<?php

/**
 * @file
 * Contains \Drupal\electricity_usage_v1\Form\UploadPDF
 */

 namespace Drupal\electricity_usage_v1\Form;
 use Drupal\Core\Database\Database;
 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;
 use Drupal\file\Entity\File;
 use Drupal\Core\File\FileSystemInterface;
 
 
 
 /**
  * Provides an upload pdf file form.
  */
 class UploadPDF extends FormBase {
     /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'upload_pdf_form';
    }

    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['file_upload_details'] = array(
          '#markup' => t('<b>The Invoice</b>'),
        );
        
        $validators = array(
          'file_validate_extensions' => array('PNG pdf JPG'),
        );
        $form['my_file'] = array(
          '#type' => 'managed_file',
          '#name' => 'my_file',
          '#title' => t('Choose invoice to upload:'),
          '#size' => 20,
          '#description' => t('PDF, PNG or JPG format only, file limit is 1MB.'),
          '#upload_validators' => $validators,
          '#upload_location' => 'public://my_files/',
        );
        
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Save'),
          '#button_type' => 'primary',
        );

        $form['data'] = array(
          '#type' => 'hidden',
          '#value' => '',
        );
        
        return $form;

    }


    public function findNumberFromString($str) {    
        $matches = [];
        preg_match_all('/[0-9]+/',$str,$matches);
        if($matches[0][1] == ''){  
          $amount= $matches[0][0]. ".00";
        }else{
          $amount= $matches[0][0]. "." .$matches[0][1];
        }
        return $amount;
    }

    private function checkFileExist($str_inv_number){
        $query = \Drupal::database();        
        $result = $query->select('electricity_usage','m')
            ->condition('invnumber','%' . $str_inv_number . '%', 'LIKE')
            ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
            ->execute()->fetchAll(\PDO::FETCH_OBJ);
        return count($result);
    }
    
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $filePath = "";
        $fileData = "";
        $fileName = "";
        $formfile = $form_state->getValue('my_file');

        if ($formfile) {
            $aNewFile = File::load(reset($formfile));
            $aNewFile->setPermanent();
            $fileName = $aNewFile->getFilename();
            $filePath = 'public://my_files/'  .$fileName. '';
            $fileData = fopen($filePath, 'r');
        }
        
        $client = new \GuzzleHttp\Client();
        $r = $client->request('POST', 'http://api.ocr.space/parse/image',[
            // 'headers' => ['apiKey' => 'helloworld'],
            'headers' => ['apiKey' => 'K81430493988957'], //free OCR API key
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $fileData
                ]
            ]
        ], ['file' => $fileData]);
        $response =  json_decode($r->getBody(),true);
        $pareValues = [];
        foreach($response['ParsedResults'] as $pareValue) {
            array_push($pareValues,$pareValue['ParsedText']);
        }

        $form_state->setValue('data',$pareValues[0]);

        //the text(a string) that OCR extracted
        $content = $pareValues[0];

        //extract key information: invoice number, quantity, date, amount, total
        //this part is hard-coded, needs ajust when invoice format changes
        $invnumberpos = strrpos($content,"INVOICE");
        $invnumber = substr($content,$invnumberpos+10,10);

        $datepos = strrpos($content,"INVOICE");
        $date = substr($content,$datepos+20,14);

        $quantitypos = strrpos($content,"Quantity");
        $quantitystr = substr($content,$quantitypos+9,6);
        $quantity = $this->findNumberFromString($quantitystr);

        $amountpos = strrpos($content,"Amount");
        $amountstr = substr($content,$amountpos+9,6);
        $amount = $this->findNumberFromString($amountstr);

        $totalstr = substr($content,-9);
        $total = $this->findNumberFromString($totalstr);
        
        //check if the invoice already exists
        if($this->checkFileExist($invnumber) > 0){
            $uri = 'public://my_files/Invoice # INV';
            $query = \Drupal::database();
            $query->delete('file_managed')
                ->condition('uri','%' . $uri . '%', 'LIKE')
                ->execute();

            //if the invoice already exists, popup an error message
            $form_state->setRedirect('electricity_usage_v1.uploadpdf', ['flag' => 0]);    
        }else{
            //store the key information into database if the invoice is a new one, then redirect to Records page
            $string_len = strval(strlen($pareValues[0]));
            $database = \Drupal::database();
            $database->insert('electricity_usage')
              ->fields(array(
                'invnumber' => $invnumber,
                'datestr' => $date,
                'quantity' => $quantity,
                // 'quantity' => $pareValues[0],  // to display the entire text for testing
                'amount' => $amount,
                'total' => $total,
              ))
              ->execute();

            $uri = 'public://my_files/Invoice # INV';
            $query = \Drupal::database();
            $query->delete('file_managed')
                ->condition('uri','%' . $uri . '%', 'LIKE')
                ->execute();
  
            $form_state->setRedirect('electricity_usage_v1.electricity',['flag' => 1]);    
        }
      }
 }