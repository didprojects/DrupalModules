(function($,Drupal, drupalSettings){
    'use strict';

    $(document).ready(function(){
        document.getElementById('upload-file-btn').addEventListener("click",function()
        {
            var flagid = 1;
            location.href="uploadpdf/"+flagid; 
        })
        document.getElementById('generate-file-btn').addEventListener("click",function()
        {
            location.href="downloadjson";
        })
        document.getElementById('edit-display-table').addEventListener("click",function()
        {
            location.href="electricity";
        })
        document.getElementById('edit-display-graph').addEventListener("click",function()
        {
            location.href="displaygraph";
        })

    })

    
    
})(jQuery, Drupal, drupalSettings);