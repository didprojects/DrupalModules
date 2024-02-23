<?php


namespace Drupal\fertiliser_nutrient_v1\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
/**
 * Controller for display Report
 */

class DownloadJsonFertiliser extends ControllerBase {
    /**
     * Creates the report page.
     * 
     * @return array
     *  Render array for report output.
     */   
    protected function load() {
        $query = \Drupal::database();
        $result = $query->select('fertiliser_nutrient_data','m')
                  ->fields('m',['id','paddock','date','areaspread','product','rate','uom','nutrient_n','nutrient_p','nutrient_k','nutrient_s','nutrient_ca','nutrient_mg'])
                ->orderBy('id')
                ->execute()->fetchAll(\PDO::FETCH_OBJ);
        return $result;
    }


    public function report() {
        
        $results = $this->load();
        foreach($results as $row){
            $month_year = "";
            $emission = 0;
            $date1 = strtr($row->date, '/', '-');
            $row->date = date('m-d-Y', strtotime($date1));
        }

        $names = array(); 
        foreach ($results as $my_object) {
            $names[] = $my_object->date; //any object field
        }
        array_multisort($names, SORT_ASC, $results);

        $tmp_year = 0;
        $emission_tmp = [];
        $temp_list = [];
        $final_list = [];
        $first = TRUE;
        $times = 0;
        foreach($results as $row){
            $times++;
            $dt = $row->date;
            $year = substr($dt,-4,4);

            if($year != $tmp_year || $times == count($results)) {
                $tmp_year = $year;
                if($first){
                   $first = FALSE;
                }else{
                    array_push($final_list,$temp_list);
                    $temp_list = [];
                }

            }
            array_push($temp_list,$row);        
        }


        $mylist = [];
        foreach($final_list as $item){
            $paddock = 0;
            $date = '';
            $sum_areaspread = 0;
            $sum_rate = 0;
            $sum_n = 0;
            $sum_p = 0;
            $sum_k = 0;
            $sum_s = 0;
            $sum_ca = 0;
            $sum_mg = 0;
            if(count($item)>0){
                foreach($item as $i){
                    $paddock = $i->paddock;
                    $date = $i->date;
                    $sum_areaspread += $i->areaspread;
                    $sum_rate += intval($i->rate);
                    $sum_n += intval($i->nutrient_n);
                    $sum_p += intval($i->nutrient_p);
                    $sum_k += intval($i->nutrient_k);
                    $sum_s += intval($i->nutrient_s);
                    $sum_ca += intval($i->nutrient_ca);
                    $sum_mg += intval($i->nutrient_mg);
                }
                
                $sum_areaspread = round($sum_areaspread,2);
                $sum_rate = round($sum_rate,2);
                $sum_n = round($sum_n,2);
                $sum_p = round($sum_p,2);
                $sum_k = round($sum_k,2);
                $sum_s = round($sum_s,2);
                $sum_ca = round($sum_ca,2);
                $sum_mg = round($sum_mg,2);
                
                $templist = [];
                array_push($templist,$paddock,$date,$sum_areaspread,$sum_rate,$sum_n,
                        $sum_p,$sum_k,$sum_s,$sum_ca,$sum_mg);

                array_push($mylist,$templist);
            }
        }

        foreach($mylist as $row){
            $json_array['fertiliser nutrient'][] = array(
                'year' => substr($row[1],-4,4),
                'areaspread' => $row[2],
                'rate' => $row[3],
                'nutrient_n' => $row[4],
                'nutrient_p' => $row[5],
                'nutrient_k' => $row[6],
                'nutrient_s' => $row[7],
                'nutrient_ca' => $row[8],
                'nutrient_mg' => $row[9],
           );        
        }


        $json_data = json_encode($json_array);
        $file_path = 'public://fertiliser.json';
        file_put_contents($file_path, $json_data);

        $headers = [
        'Content-Type' => 'text/csv/json', // Would want a condition to check for extension and set Content-Type dynamically
        'Content-Description' => 'File Download',
        'Content-Disposition' => 'attachment; filename=fertiliser.json'
        ];

        // Return and trigger file donwload.
        return new BinaryFileResponse($file_path, 200, $headers, true );   
    }
}

