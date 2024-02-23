<?php


namespace Drupal\electricity_usage_v1\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
/**
 * Controller for display Report
 */

class DownloadJson extends ControllerBase {
    /**
     * Creates the report page.
     * 
     * @return array
     *  Render array for report output.
     */   
    protected function load() {
        $cur_year = date("Y");
        $query = \Drupal::database();
        $result = $query->select('electricity_usage','m')
                ->fields('m',['invid','invnumber','datestr','quantity','amount','total'])
                ->condition('datestr','%' . $cur_year . '%', 'LIKE')
                ->execute()->fetchAll(\PDO::FETCH_OBJ);
        return $result;
    }


    public function report() {
        foreach ($result = $this->load() as $row) {
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

        $cur_year = date("Y");
        $total = array(
            'year' => $cur_year,
            'total_usage' => $total_usage,
            'total_charge' => $total_charge,
        );
      
        $data = [$total];

        foreach($data as $row){
            $json_array['electricity usage'][] = array(
                'current_year' => $row['year'],
                'total_usage' => $row['total_usage'],
                'total_charge' => $row['total_charge'],
           );        
        }


        $json_data = json_encode($json_array);
        $file_path = 'public://electricity.json';
        file_put_contents($file_path, $json_data);

        $headers = [
        'Content-Type' => 'text/csv/json', // Would want a condition to check for extension and set Content-Type dynamically
        'Content-Description' => 'File Download',
        'Content-Disposition' => 'attachment; filename=electricity.json'
        ];

        // Return and trigger file donwload.
        return new BinaryFileResponse($file_path, 200, $headers, true );   
    }
}

