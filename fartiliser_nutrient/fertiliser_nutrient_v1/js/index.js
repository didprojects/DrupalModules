(function($,Drupal, drupalSettings){
    'use strict';

    $(document).ready(function(){

        document.getElementById('download-json-fertiliser').addEventListener("click",function()
        {
            location.href="downloadjsonfertiliser"; 
        })
        document.getElementById('upload-csv-btn').addEventListener("click",function()
        {
            location.href="uploadcsv"; 
        })
    })  
})(jQuery, Drupal, drupalSettings); 