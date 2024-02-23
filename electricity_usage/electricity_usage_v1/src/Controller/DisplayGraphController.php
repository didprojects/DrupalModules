<?php
/**
 * @file
 * Contains \Drupal\electricity_usage_v1\Controller\DisplayGraphController.
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



class DisplayGraphController extends ControllerBase {
    /**
     * Creates the report page.
     * 
     * @return array
     *  Render array for report output.
     */   

    //load electricity usage data from electricity_usage table
    protected function load($select_result,$cnt,$flag=0) {
        $query = \Drupal::database();
        if($cnt>0){
            $results = [];
            for($i = 0; $i < $cnt; $i++){
                $year = $select_result[$i];
                $result = $query->select('electricity_usage','m')
                    ->condition('datestr','%' . $year . '%', 'LIKE')
                    ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                    ->execute()->fetchAll(\PDO::FETCH_OBJ);
                array_push($results,$result);
            }   
            return $results; 
        }else{
            $current_year = date("Y");
            $year = $select_result[0];
            if($flag==1){
                $result = $query->select('electricity_usage','m')
                ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                ->execute()->fetchAll(\PDO::FETCH_OBJ);
            }
            if($flag == 0 && $year == ""){
                $result = $query->select('electricity_usage','m')   
                ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                ->condition('datestr','%' . $current_year . '%', 'LIKE')
                ->execute()->fetchAll(\PDO::FETCH_OBJ);
            }elseif($flag == 0 && $year != "" ){
                $result = $query->select('electricity_usage','m')
                ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                ->condition('datestr','%' . $year . '%', 'LIKE')
                ->execute()->fetchAll(\PDO::FETCH_OBJ);
            }
            
            return $result;
        }
    }

    public function displayGraph() {
        $form_state = new \Drupal\Core\Form\FormState();
        $form_state->setRebuild();
        $simpleform = \Drupal::formBuilder()->buildForm('Drupal\electricity_usage_v1\Form\DisplayGraph',$form_state);
        $select_result=[];
        $select_result = $form_state->getValue('choice');
        
        $indexes = [];
        foreach($select_result as $key => $sindex){
            array_push($indexes,$sindex);
        }
        
        $invoice_dates = [];
        $result = $this->load([],0,1);
        foreach($result as $rr){
            $rrr = strtotime (trim($rr->datestr));
            $rr->datestr = date('Y-m-d',$rrr); 
        }
        
        $keys = array_column($result, 'datestr');
        array_multisort($keys, SORT_DESC, $result);

        foreach ($result as $row) {
            $invoice_dates[] = trim(substr($row->datestr,0,4));
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
        sort($years);

        //$y is the selected year, can be 1 or 2 or more
        $y = [];
        foreach($indexes as $idx){
            array_push($y,$years[$idx]);
        }
        

        $cnt = count($y);

        $datas = [];
        $months = [];
        $labelyear_one = '' ;
        $labelyear = '' ;
        $labelyears = [];
        if($cnt == 0){   //default page or no selected year
            $data = [];
            $month_one = [];

            $result = $this->load($y,$cnt);
            foreach($result as $rr){
                $rrr = strtotime (trim($rr->datestr));
                $rr->datestr = date('Y-m-d',$rrr); 
            }
            $keys = array_column($result, 'datestr');
            array_multisort($keys, SORT_DESC, $result);

            foreach ($result as $row) {
                $data[] = intval($row->total);

                $rrr = strtotime (trim($row->datestr));
                $row->datestr = date('M d, Y',$rrr); 

                $month_one[] = trim(substr($row->datestr,0,4));
                if($labelyear_one == ''){
                    $labelyear_one = substr($row->datestr,-5);
                }
            }
        }else{
            $results = $this->load($y,$cnt);
            foreach ($results as $rr) {
                foreach($rr as $dt){
                    $rrr = strtotime (trim($dt->datestr));
                    $dt->datestr = date('Y-m-d',$rrr); 
                }
                $keys = array_column($rr, 'datestr');
                array_multisort($keys, SORT_DESC, $rr);
    

                $data = [];
                $month = [];
                foreach ($rr as $item) {
                    $rrr = strtotime (trim($item->datestr));
                    $item->datestr = date('M d, Y',$rrr); 
    
                    $data[] = intval($item->total);
                    $month[] = trim(substr($item->datestr,0,4));
                    $labelyear = substr($item->datestr,-5);
                }
                array_push($labelyears,$labelyear);
                array_push($datas,$data);
                array_push($months,$month);
            }
        }
        

        $charts_settings = $this->config('charts.settings');
        $library = $charts_settings->get('charts_default_settings.library');
        if (empty($library)) {
            $this->messenger->addError($this->t('You need to first configure Charts default settings'));
            return [];
        }

        // This is a container for all the charts below.
        $charts_container = [
            '#type' => 'container',
            'content' => [],
        ];

        $chart_types = ['line'];
        
        // x axis label
        $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        $data_modify = [];
        $datas_modify = [];
        $labels_len = count($labels);
        if(count($datas)==0){
            for($i=0; $i<$labels_len; $i++){
                if(!in_array($labels[$i], $month_one)){
                    $data_modify[$i] = NULL;
                }
                else{
                    $idx = array_keys($month_one,$labels[$i]);
                    $data_modify[$i] = $data[$idx[0]];
                }
            }
        }else{
            for($j=0;$j<count($datas);$j++){
                for($i=0; $i<$labels_len; $i++){
                    if(!in_array($labels[$i], $months[$j])){
                        $datas_modify[$j][$i] = NULL;
                    }
                    else{
                        $idx = array_keys($months[$j],$labels[$i]);
                        $datas_modify[$j][$i] = $datas[$j][$idx[0]];
                    }                
                }
            }
        }
        
                
        $series_multiple = [];
        if(count($datas)==0){
            $series = [
                '#type' => 'chart_data',
                '#title' => $labelyear_one,
                '#data' => $data_modify,
                '#color' => '#1d84c3',
            ];
        }
        else{
            // at most 20 years for comparison
            $series_nums_array = ['one','two','three','four','five','six','seven','eight','nine','ten','eleven'.'twelve',
                                    'thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen','twenty'];
            if(count($datas) <= 20){
                for($i=0;$i<count($datas);$i++){
                    $rand_color = '#' . substr(md5(mt_rand()), 0, 6);
                    if($i==0){
                        $series = [
                            '#type' => 'chart_data',
                            '#title' => $labelyears[$i],
                            '#data' => $datas_modify[$i],
                            '#color' => '#1d84c3',
                        ];
                    }else{
                        $series_str = 'series_' . $series_nums_array[$i-1]. '';    
                        ${$series_str} = array();
                        ${$series_str} = [
                            '#type' => 'chart_data',
                            '#title' => $labelyears[$i],
                            '#data' => $datas_modify[$i],
                            '#color' => $rand_color,
                        ];
                    }
                }
            }
        }
     
        // Define an x-axis to be used in multiple examples.
        $xaxis = [
            '#type' => 'chart_xaxis',
            '#title' => $this->t('Electricity Usage Month'),
            '#labels' => $labels,
        ];

        // Define a y-axis to be used in multiple examples.
        $yaxis = [
            '#type' => 'chart_yaxis',
            '#title' => $this->t('Eletricity Usage Total (US$)'),
        ];

        // Iterate through the chart types and build a chart for each.
        if(count($datas)==0){
            foreach ($chart_types as $type) {
                $charts_container['content'][$type] = [
                    '#type' => 'chart',
                    '#tooltips' => $charts_settings->get('charts_default_settings.display.tooltips'),
                    '#title' => $this->t('@library @type Chart', [
                    '@library' => ucfirst($library),
                    '@type' => ucfirst($type),
                    ]),
                    '#chart_type' => $type,
                    'series' => $series,
                    'x_axis' => $xaxis,
                    'y_axis' => $yaxis,
                    '#raw_options' => [],
                ];
                }
        }else{
            foreach ($chart_types as $type) {
                $charts_container['content'][$type] = [
                    '#type' => 'chart',
                    '#tooltips' => $charts_settings->get('charts_default_settings.display.tooltips'),
                    '#title' => $this->t('@library @type Chart', [
                    '@library' => ucfirst($library),
                    '@type' => ucfirst($type),
                    ]),
                    '#chart_type' => $type,
                    'series' => $series,
                    'series_one' => $series_one,
                    'series_two' => $series_two,
                    'series_three' => $series_three,
                    'series_four' => $series_four,
                    'series_five' => $series_five,
                    'series_six' => $series_six,
                    'series_seven' => $series_seven,
                    'series_eight' => $series_eight,
                    'series_nine' => $series_nine,
                    'series_ten' => $series_ten,
                    'series_eleven' => $series_eleven,
                    'series_twelve' => $series_twelve,
                    'series_thirteen' => $series_thirteen,
                    'series_fourteen' => $series_fourteen,
                    'series_fifteen' => $series_fifteen,
                    'series_sixteen' => $series_sixteen,
                    'series_seventeen' => $series_seventeen,
                    'series_eighteen' => $series_eighteen,
                    'series_nineteen' => $series_nineteen,
                    'series_twenty' => $series_twenty,
                    'x_axis' => $xaxis,
                    'y_axis' => $yaxis,
                    '#raw_options' => [],
                ];    
                
            }
        }

        // enable css and js to this controller
        $charts_container['#attached']['library'][] = 'electricity_usage_v1/electricity_usage_v1_js_css';

        return [$simpleform,$charts_container];
    }

}
