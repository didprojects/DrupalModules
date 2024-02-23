<?php

/**
 * @file
 * Contains \Drupal\fertiliser_nutrient_v1\Form\FertiliserNutrientForm
 */

namespace Drupal\fertiliser_nutrient_v1\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides an Fruit name form.
 */
class FertiliserNutrientForm extends FormBase {
    /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'fertiliser_nutrient_form';
    }

    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $node = \Drupal::routeMatch()->getParameter('node');
        $nid = $node->nid->value;


        $form['search'] = array(
            '#type' => 'textfield',
            '#placeholder' => 'Search by paddock...',
            '#size' => 25,
        );
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Search'),
        );

        $form['btn2'] = array(
            '#type' => 'textfield',
            '#value' => t('Upload CSV File'),
            '#id' => 'upload-csv-btn',
        );

     
        $form['btn-json-download'] = array(
            '#type' => 'textfield',
            '#value' => t('Download JSON for Overseer'),
            '#id' => 'download-json-fertiliser',
        );
    
        $form['nid'] = array(
            '#type' => 'hidden',
            '#value' => $nid,
        );

        // enable css and js to this form
        $form['#attached']['library'][] = 'fertiliser_nutrient_v1/fertiliser_nutrient_v1_js_css';
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