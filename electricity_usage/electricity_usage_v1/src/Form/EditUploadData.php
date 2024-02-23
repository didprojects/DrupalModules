<?php

/**
 * @file
 * Contains \Drupal\electricity_usage_v1\Form\EditUploadData
 */

namespace Drupal\electricity_usage_v1\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides an Fruit name form.
 */
class EditUploadData extends FormBase {
    /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'edit_upload_data';
    }

    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $id = \Drupal::routeMatch()->getParameter('invid');
        $query = \Drupal::database();
        $data = $query->select('electricity_usage','m')
                ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                ->condition('m.invid',$id,'=')
                ->execute()->fetchAll(\PDO::FETCH_OBJ);
        

        $form['invnumber'] = array(
            '#title' => t('Invoice Number'),
            '#type' => 'textfield',
            '#required' => TRUE,
            '#default_value' => $data[0]->invnumber,
        );
        $form['datestr'] = array(
            '#title' => t('Invoice Date'),
            '#type' => 'textfield',
            '#required' => TRUE,
            '#default_value' => $data[0]->datestr,
        );
        $form['quantity'] = array(
            '#title' => t('Usage'),
            '#type' => 'textfield',
            '#required' => TRUE,
            '#default_value' => $data[0]->quantity,
        );
        $form['amount'] = array(
            '#title' => t('Amount'),
            '#type' => 'textfield',
            '#required' => TRUE,
            '#default_value' => $data[0]->amount,
        );
        $form['total'] = array(
            '#title' => t('Total'),
            '#type' => 'textfield',
            '#required' => TRUE,
            '#default_value' => $data[0]->total,
        );
        $form['update'] = array(
            '#type' => 'submit',
            '#value' => t('Update'),
        );
        return $form;
    }

    /**
     * (@inheritdoc)
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $id = \Drupal::routeMatch()->getParameter('invid');
        $postdata = $form_state->getValues();

        unset($postdata['update'],$postdata['form_build_id'],$postdata['form_token'],$postdata['form_id'],$postdata['op']);
        $query = \Drupal::database();
        $query->update('electricity_usage')->fields($postdata)
              ->condition('invid',$id)
              ->execute();

        $response = new \Symfony\Component\HttpFoundation\RedirectResponse('../electricity');
        $response->send();
        
        $messenger = \Drupal::messenger();
        $messenger->addMessage($this->t('Data updated successfully!'));
    }
}