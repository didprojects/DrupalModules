<?php


namespace Drupal\gait_flux_v4\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
/**
 * Controller for display Report
 */

class DownloadJsonFlux extends ControllerBase {
    /**
     * Creates the report page.
     * 
     * @return array
     *  Render array for report output.
     */   
    protected function load() {
        $query = \Drupal::database();
        $result = $query->select('gaitflux','m')
                ->fields('m',['deviceid','date','emission','below_soil_absorption','absorption','amb_pressure','amb_temp','co2_ppm','h2o','wind_ux','wind_uy','wind_uz','co2_mgm3'])
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
            $deviceid = 0;
            $date = '';
            $sum_emission = 0;
            $sum_bsabsorp = 0;
            $sum_absorp = 0;
            $sum_ambpre = 0;
            $sum_ambtemp = 0;
            $sum_co2 = 0;
            $sum_h2o = 0;
            $sum_ux = 0;
            $sum_uy = 0;
            $sum_uz = 0;
            $sum_cc = 0;
            if(count($item)>0){
                foreach($item as $i){
                    $deviceid = $i->deviceid;
                    $date = $i->date;
                    $sum_emission += $i->emission;
                    $sum_bsabsorp += $i->below_soil_absorption;
                    $sum_absorp += $i->absorption;
                    $sum_ambpre += $i->amb_pressure;
                    $sum_ambtemp += $i->amb_temp;
                    $sum_co2 += $i->co2_ppm;
                    $sum_h2o += $i->h2o;
                    $sum_ux += $i->wind_ux;
                    $sum_uy += $i->wind_uy;
                    $sum_uz += $i->wind_uz;
                    $sum_cc += $i->co2_mgm3;
                }
                $cnt = count($item);
                $sum_emission_avg = round($sum_emission/$cnt,2);
                $sum_bsabsorp_avg = round($sum_bsabsorp/$cnt,2);
                $sum_absorp_avg = round($sum_absorp/$cnt,2);
                $sum_ambpre_avg = round($sum_ambpre/$cnt,2);
                $sum_ambtemp_avg = round($sum_ambtemp/$cnt,2);
                $sum_co2_avg = round($sum_co2/$cnt,2);
                $sum_h2o_avg = round($sum_h2o/$cnt,2);
                $sum_ux_avg = round($sum_ux/$cnt,2);
                $sum_uy_avg = round($sum_uy/$cnt,2);
                $sum_uz_avg = round($sum_uz/$cnt,2);
                $sum_cc_avg = round($sum_cc/$cnt,2);
                $templist = [];
                array_push($templist,$deviceid,$date,$sum_emission_avg,$sum_bsabsorp_avg,$sum_absorp_avg,
                        $sum_ambpre_avg,$sum_ambtemp_avg,$sum_co2_avg,$sum_h2o_avg,$sum_ux_avg,$sum_uy_avg,$sum_uz_avg,$sum_cc_avg);

                array_push($mylist,$templist);
            }
        }
       
        foreach($mylist as $row){
            $json_array['gait flux'][] = array(
                'year' => substr($row[1],-4,4),
                'emission' => $row[2],
                'below_soil_absorption' => $row[3],
                'absorption' => $row[4],
                'amb_pressure' => $row[5],
                'amb_temp' => $row[6],
                'co2_ppm' => $row[7],
                'h2o' => $row[8],
                'wind_ux' => $row[9],
                'wind_uy' => $row[10],
                'wind_uz' => $row[11],
                'co2_mgm3' => $row[12],
           );        
        }

        $json_data = json_encode($json_array);
        $file_path = 'public://gaitflux.json';
        file_put_contents($file_path, $json_data);

        $headers = [
        'Content-Type' => 'text/csv/json', // Would want a condition to check for extension and set Content-Type dynamically
        'Content-Description' => 'File Download',
        'Content-Disposition' => 'attachment; filename=gaitflux.json'
        ];

        // Return and trigger file donwload.
        return new BinaryFileResponse($file_path, 200, $headers, true );   
    }
}

