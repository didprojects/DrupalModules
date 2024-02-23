<?php

/**
 * @file
 * Contains \Drupal\electricity_usage_v1\Form\UploadSearch
 */

namespace Drupal\electricity_usage_v1\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides an Fruit name form.
 */
class ElectricityUsageForm extends FormBase {
    /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'upload_pdf_search';
    }

    protected function load() {
     
        $query = \Drupal::database();
        $result = $query->select('electricity_usage','m')
                ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                ->orderBy('datestr')
                ->execute()->fetchAll(\PDO::FETCH_OBJ);
        return $result;
    }

    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $node = \Drupal::routeMatch()->getParameter('node');
        $nid = $node->nid->value;

        
        $form['display'] = array(
            '#type' => 'radios',
            '#default_value' => 'Table',
            '#options' => array(
                'Table'=>'Records',
                'Graph'=>'Graph',
            ),
            '#required' => TRUE,
        );


        //add a drop down
        $invoice_dates = [];
        foreach ($result = $this->load() as $row) {
            $invoice_dates[] = trim(substr($row->datestr,-5));
        }

        $str = array_unique($invoice_dates);
        $years = [];
        foreach($str as $year){
            $matches = [];
            preg_match_all('/[0-9]+/',$year,$matches);
            if($matches[0][0] != ''){
                $years[] = $matches[0][0];
            }    
        }
        krsort($years);
        $options = array(
            'Select Year' => $years,
        );
        $form['choice'] = array(
            '#type' => 'select',
            '#options' => $options,
            '#multiple' => FALSE,   
            '#id' => "dropdown-year"
        );
        $form['submit1'] = array(
            '#type' => 'submit',
            '#value' => t('Go'),
        );


        $form['search'] = array(
            '#type' => 'textfield',
            '#placeholder' => 'Search by date...',
            '#size' => 25,
        );
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Search'),
        );

        $form['btn1'] = array(
            '#type' => 'textfield',
            '#value' => t('Add New Invoice'),
            '#id' => 'upload-file-btn',
        );
        $form['btn2'] = array(
            '#type' => 'textfield',
            '#value' => t('Download Json for Overseer'),
            '#id' => 'generate-file-btn',
        );


        $form['nid'] = array(
            '#type' => 'hidden',
            '#value' => $nid,
        );

        // enable css and js to this form
        $form['#attached']['library'][] = 'electricity_usage_v1/electricity_usage_v1_js_css';
        
        return $form;
    }

    /**
     * (@inheritdoc)
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $messenger = \Drupal::messenger();
        $messenger->addMessage($this->t('Search completed.'));
    }
}