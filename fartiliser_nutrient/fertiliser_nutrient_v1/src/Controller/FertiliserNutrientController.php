<?php
/**
 * @file
 * Contains \Drupal\fertiliser_nutrient_v1\Controller\FertiliserNutrientController.
 */
namespace Drupal\fertiliser_nutrient_v1\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;

/**
 * Controller for display Report
 */

class FertiliserNutrientController extends ControllerBase {
    /**
     * Creates the report page.
     * 
     * @return array
     *  Render array for report output.
     */   
   
    protected function load($search_result) {
      $limit = 10;
      if($search_result == ""){
          $query = \Drupal::database();
          $result = $query->select('fertiliser_nutrient_data','m')
                  ->fields('m',['id','paddock','date','areaspread','product','rate','uom','nutrient_n','nutrient_p','nutrient_k','nutrient_s','nutrient_ca','nutrient_mg'])
                  // ->orderBy('id')
                  ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit)
                  ->execute()->fetchAll(\PDO::FETCH_OBJ);
          return $result;
      }else{
          $query = \Drupal::database();
          $result = $query->select('fertiliser_nutrient_data','m')
                  ->condition('paddock','%' . $search_result . '%', 'LIKE')
                  // ->orderBy('id')
                  ->fields('m',['id','paddock','date','areaspread','product','rate','uom','nutrient_n','nutrient_p','nutrient_k','nutrient_s','nutrient_ca','nutrient_mg'])
                  ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit)
                  ->execute()->fetchAll(\PDO::FETCH_OBJ);
          return $result;
      }
  }



    public function report() {

        $content = array();

        $form_state_search = new \Drupal\Core\Form\FormState();
        $form_state_search->setRebuild();
        $search_form = \Drupal::formBuilder()->buildForm('Drupal\fertiliser_nutrient_v1\Form\FertiliserNutrientForm',$form_state_search);
        $search_result = $form_state_search->getValue('search');

        $results = $this->load($search_result);
        foreach($results as $row){
             $data[] = [
                 'paddock' => $row->paddock,
                 'date' => $row->date,
                 'areaspread' => $row->areaspread,
                 'product' => $row->product,
                 'rate' => $row->rate,
                 'uom' => $row->uom,
                 'nutrient_n' => $row->nutrient_n,
                 'nutrient_p' => $row->nutrient_p,
                 'nutrient_k' => $row->nutrient_k,
                 'nutrient_s' => $row->nutrient_s,
                 'nutrient_ca' => $row->nutrient_ca,
                 'nutrient_mg' => $row->nutrient_mg,
             ];
        }


        $headers = array('Paddock','Date','Area Spread(ha)','Product','Rate','UOM','N(kg/ha)','P(kg/ha)','K(kg/ha)','S(kg/ha)','Ca(kg/ha)','Mg(kg/ha)');
        $content['table'] = array(
          '#type' => 'table',
          '#header' => $headers,
          '#rows' => $data,
          '#empty' => t('No data available.'),
        );

        $content['parger'] = [
          '#type' => 'pager'
        ];

        $content['#cache']['max-age'] = 0;
        return [$search_form,$content];
    }
}