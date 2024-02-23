<?php

/**
 * @file
 * Contains \Drupal\gait_flux_v4\Form\GaitFluxForm
 */

namespace Drupal\gait_flux_v4\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides an Fruit name form.
 */
class GaitFluxForm extends FormBase {
    /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'gait_flux_form';
    }

    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $node = \Drupal::routeMatch()->getParameter('node');
        $nid = $node->nid->value;

        $form['search'] = array(
            '#type' => 'textfield',
            '#placeholder' => 'Search by month or year...',
            '#size' => 25,
        );
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Search'),
        );
        
        // $form['btn1'] = array(
        //     '#type' => 'textfield',
        //     '#value' => t('Retrieve Data From Ag Database'),
        //     '#id' => 'retrive-external-data-btn',
        // );

        $form['btn-json-download'] = array(
            '#type' => 'textfield',
            '#value' => t('Download JSON for Overseer'),
            '#id' => 'download-json-flux',
        );

         // enable css and js to this form
        $form['#attached']['library'][] = 'gait_flux_v4/gait_flux_v4_js_css';
        return $form;
    }

    /**
     * (@inheritdoc)
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $messenger = \Drupal::messenger();
        $messenger->addMessage($this->t('Retrieve data completed.'));
    }
}