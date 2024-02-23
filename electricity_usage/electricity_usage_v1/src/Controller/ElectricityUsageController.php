<?php
/**
 * @file
 * Contains \Drupal\electricity_usage_v1\Controller\ElectricityUsageController.
 */
namespace Drupal\electricity_usage_v1\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Dompdf\Dompdf;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Uuid\UuidInterface;



class ElectricityUsageController extends ControllerBase {
    /**
     * Creates the report page.
     * 
     * @return array
     *  Render array for report output.
     */   


    /**
     * The messenger service.
     *
     * @var \Drupal\Core\Messenger\MessengerInterface
     */
    protected $messenger;

    /**
     * The UUID service.
     *
     * @var \Drupal\Component\Uuid\UuidInterface
     */
    protected $uuidService;

    /**
     * The module extension list.
     *
     * @var \Drupal\Core\Extension\ModuleExtensionList
     */
    protected $moduleList;

    /**
     * Construct.
     *
     * @param \Drupal\Core\Messenger\MessengerInterface $messenger
     *   The messenger service.
     * @param \Drupal\Component\Uuid\UuidInterface $uuidService
     *   The UUID service.
     * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
     *   The module list.
     */
    public function __construct(MessengerInterface $messenger, UuidInterface $uuidService, ModuleExtensionList $module_list) {
        $this->messenger = $messenger;
        $this->uuidService = $uuidService;
        $this->moduleList = $module_list;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
        $container->get('messenger'),
        $container->get('uuid'),
        $container->get('extension.list.module')
        );
    }

    protected function load($search_result) {
        $limit = 100;
        if($search_result == ""){
            $query = \Drupal::database();
            $result = $query->select('electricity_usage','m')
                    ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                    ->orderBy('invid')
                    ->execute()->fetchAll(\PDO::FETCH_OBJ);
            return $result;
        }else{
            $query = \Drupal::database();
            $result = $query->select('electricity_usage','m')
                    ->condition('datestr','%' . $search_result . '%', 'LIKE')
                    ->orderBy('invid')
                    ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit)
                    ->execute()->fetchAll(\PDO::FETCH_OBJ);
            return $result;
        }
    }

    protected function load_year($result_year) {
        if($result_year == ""){
            $current_year = date("Y");
            $query = \Drupal::database();
            $result = $query->select('electricity_usage','m')
                    ->condition('datestr','%' . $current_year . '%', 'LIKE')
                    ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                    ->orderBy('invid')
                    // ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit)
                    ->execute()->fetchAll(\PDO::FETCH_OBJ);
            return $result;
        }else{
            $query = \Drupal::database();
            $result = $query->select('electricity_usage','m')
                    ->condition('datestr','%' . $result_year . '%', 'LIKE')
                    ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                    ->orderBy('invid')
                    ->execute()->fetchAll(\PDO::FETCH_OBJ);
            return $result;
        }
    }
 
    public function uploadFile() {
        $content = array();

        // add dropdown and search form
        $form_state = new \Drupal\Core\Form\FormState();
        $form_state->setRebuild();
        $simpleform = \Drupal::formBuilder()->buildForm('Drupal\electricity_usage_v1\Form\ElectricityUsageForm',$form_state);
        $search_result = $form_state->getValue('search');
        $filter_result = $form_state->getValue('choice');

        // add current year summary form
        $form_state_summary = new \Drupal\Core\Form\FormState();
        $form_state_summary->setRebuild();
        $form_cur_summary = \Drupal::formBuilder()->buildForm('Drupal\electricity_usage_v1\Form\CurrentYearSummary',$form_state_summary);


        //get the years from database
        $invoice_dates = [];
        foreach ($result = $this->load("") as $row) {
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
        $filter_year = '';
        foreach($years as $y){
            $filter_year = $years[$filter_result];
        }


        $data=[];
        $count = 0;
        $limit = 100;
        $params = \Drupal::request()->query->all();
        if(empty($params) || $params['page'] == 0){
            $count = 1;
        }else if($params['page'] == 1){
            $count = $params['page'] + $limit;
        }else{
            $count = $params['page'] + $limit;
            $count++;
        }

        // table header
        $headers = array('S_No','Invoice Number','Date','Usage(kwh)','Amount(US$)', 'Total(US$)','Edit','Delete');
        $rows = array();
        

        if($search_result == ''){
            $result = $this->load_year($filter_year);
        }else{
            $result = $this->load($search_result);
        }
        
        //sort the rows by date
        foreach($result as $rr){
            $rrr = strtotime (trim($rr->datestr));
            $rr->datestr = date('Y-m-d',$rrr); 
        }
        $keys = array_column($result, 'datestr');
        array_multisort($keys, SORT_DESC, $result);

        foreach ($result as $row) {
            //Sanitize each entry.
            $rr = strtotime (trim($row->datestr));
            $row->datestr = date('M d, Y',$rr); 
            $data[] = [
                'serial_no' => $count.".",
                'invnumber' => $row->invnumber,
                'datestr' => $row->datestr,
                'quantity' => $row->quantity,
                'amount' => $row->amount,
                'total' => $row->total,
                'edit' => t("<a href='edit-upload-data/$row->invid'>Edit</a>"),
                'delete' => t("<a href='delete-upload-data/$row->invid'>Delete</a>")
            ];
            $count++;
        }
      
        $content['table'] = array(
            '#type' => 'table',
            '#header' => $headers,
            '#rows' => $data,
            '#empty' => t('No data available.'),
        );
        $content['parger'] = [
            '#type' => 'pager'
        ];
        //Don't cache this page.
        $content['#cache']['max-age'] = 0;

        // enable css and js to this controller
        $content['#attached']['library'][] = 'electricity_usage_v1/electricity_usage_v1_js_css';

        return [$simpleform,$content,$form_cur_summary];
    }
    
    /** Delete data **/
    public function deleteData($invid){
        $query = \Drupal::database();
        $query->delete('electricity_usage')
            ->condition('invid',$invid,'=')
            ->execute();

        $response = new \Symfony\Component\HttpFoundation\RedirectResponse('../electricity');
        $response->send();

        $messenger = \Drupal::messenger();
        $messenger->addMessage($this->t('Data deleted successfully!'));
    }
}
