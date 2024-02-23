<?php

/**
 * @file
 * Contains \Drupal\fertiliser_nutrient_v1\Form\UploadCSV
 */

 namespace Drupal\fertiliser_nutrient_v1\Form;
 use Drupal\Core\Database\Database;
 use Drupal\Core\Form\FormBase;
 use Drupal\Core\Form\FormStateInterface;
 use Drupal\file\Entity\File;
 
 
 /**
  * Provides an upload pdf file form.
  */
 class UploadCSV extends FormBase {
     /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'upload_csv_form';
    }

    protected function load() {
      $query = \Drupal::database();
      $result = $query->select('fertiliser_nutrient_data','m')
              ->fields('m',['paddock','date','areaspread','product','rate'])
              ->execute()->fetchAll(\PDO::FETCH_OBJ);
      return $result;
    }


    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
          
          $form['file_upload_details'] = array(
            '#markup' => t('<b>The CSV File</b>'),
          );
          
          $validators = array(
            'file_validate_extensions' => array('csv xlsx'),
          );
          $form['my_file'] = array(
            '#type' => 'managed_file',
            '#name' => 'my_file',
            '#title' => t('Choose csv file to upload:'),
            '#size' => 20,
            '#description' => t('CSV format only.'),
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

    public function validateForm(array &$form, FormStateInterface $form_state) {    
        if ($form_state->getValue('my_file') == NULL) {
          $form_state->setErrorByName('my_file', $this->t('File Error'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $fileData = "";
        $fileName = "";
        $formfile = $form_state->getValue('my_file');

        if ($formfile) {
            $aNewFile = File::load(reset($formfile));
            $aNewFile->setPermanent();
            $fileName = $aNewFile->getFilename();
            $filePath = 'public://my_files/'  .$fileName. '';
            $fileData = fopen($filePath, 'r');

            $row = fgetcsv($fileData);
            $columns = array();
            foreach ($row as $i => $header) {
              $columns[$i] = trim($header);
            }
            
            $result = $this->load();

            $cur_data = [];
            foreach($result as $re){
                $cur_item = [];
                $dt = strtotime($re->date);
                array_push($cur_item,$re->paddock,$dt,$re->areaspread,trim($re->product),$re->rate);
                array_push($cur_data,$cur_item);
            }

            $database = \Drupal::database();
            while($row = fgetcsv($fileData)){
              
              //prevent same data insert 
              if(count($cur_data) != 0){
                $dt = strtotime($row[1]);
                $new_data = [$row[0],$dt,$row[2],trim($row[3]),$row[4]];
                if(in_array($new_data,$cur_data)){
                    continue;
                }
              }
              
              //if paddock id changes,this code needs to be modified
              $entity_id = 0;
              $paddock = intval($row[0]);
              if($paddock == 2){
                 $entity_id = 1;
              }elseif($paddock == 3){
                $entity_id = 2;
              }elseif($paddock == 4){
                $entity_id = 3;
              }elseif($paddock == 41){
                $entity_id = 4;
              }elseif($paddock == 42){
                $entity_id = 5;
              }elseif($paddock == 5){
                $entity_id = 6;
              }elseif($paddock == 6){
                $entity_id = 7;
              }elseif($paddock == 7){
                $entity_id = 8;
              }elseif($paddock == 8){
                $entity_id = 9;
              }else{
                $entity_id = 0;
              }
              
              //entity_id being 0 means that the paddock doesn't exist in the system
              $database->insert('fertiliser_nutrient_data')
                ->fields(array(
                  'entity_id' => $entity_id,
                  'paddock' => $row[0],
                  'date' => $row[1],
                  'areaspread' => $row[2],
                  'product' => $row[3],
                  'rate' => $row[4],
                  'uom' => $row[5],
                  'nutrient_n' => $row[6],
                  'nutrient_p' => $row[7],
                  'nutrient_k' => $row[8],
                  'nutrient_s' => $row[9],
                  'nutrient_ca' => $row[10],
                  'nutrient_mg' => $row[11],
                ))
                ->execute();
            }
            fclose($fileData);

            //clear the file uri in the file_managed table, ensure the file can be uploaded again if needed
            $uri = 'public://my_files/fertiliser';
            $query = \Drupal::database();
            $query->delete('file_managed')
                ->condition('uri','%' . $uri . '%', 'LIKE')
                ->execute();

            
            $form_state->setRedirect('fertiliser_nutrient_v1.fertiliser');    
        } 
      }
 }