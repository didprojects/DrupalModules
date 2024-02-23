<?php

/**
 * @file
 * Contains \Drupal\electricity_usage_v1\Form\CurrentYearInfo
 */

namespace Drupal\electricity_usage_v1\Form;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides an Fruit name form.
 */
class CurrentYearSummary extends FormBase {
    /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'current-year-info';
    }

    protected function load() {
     
        $query = \Drupal::database();
        $current_year = date("Y");
        // $current_year = '2022'; // for test
        $result = $query->select('electricity_usage','m')
                ->condition('datestr','%' . $current_year . '%', 'LIKE')
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
        
        $result = $this->load();
        $num = count($result);

        $total_usage = 0;
        $total_charge = 0;
        foreach ($result as $row) {
            $data[] = [
                'invnumber' => $row->invnumber,
                'datestr' => $row->datestr,
                'quantity' => $row->quantity,
                'amount' => $row->amount,
                'total' => $row->total,
            ];
            $total_usage += $row->quantity;
            $total_charge += $row->total;
        }

        $form['summary'] = array(
            '#markup' => 'Current Year Summary: <br><br>',
            '#id' =>'summary0'
        );

        if($num < 12){
            $form['summary1'] = array(
                '#markup' => 'Usage (kWh):' .str_repeat("&nbsp;", 2) .$total_usage. str_repeat("&nbsp;", 15) . $num . ' records',
                '#id' =>'summary1'
            );
        }else{
            // when all the 12 months' records have been uploaded, inform user the completion state
            $form['summary1'] = array(
                '#markup' => 'Annual Usage (kWh):' .str_repeat("&nbsp;", 2) .$total_usage.  str_repeat("&nbsp;", 15) . $num . ' records',
                '#id' =>'summary1'
            );
            $form['summary2'] = array(
                '#markup' => '<br><br> Annual Record Complete',
                '#id' =>'summary2'
            );    
        }

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