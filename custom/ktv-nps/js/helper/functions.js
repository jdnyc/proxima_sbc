Ext.ns('Ariel.helper');

Ariel.helper.postDownload = function(action, params ){
    var isFrame = document.getElementsByName('_excelDownload');
    if( isFrame.length > 0) {
        var form = document.getElementsByName('_excelDownloadForm')[0];
        form.submit();
    }else{
        var doc = document,
        frame = doc.createElement('iframe');
        frame.style.display = 'none';
        frame.name = '_excelDownload';
        doc.body.appendChild(frame);
        form = doc.createElement('form');
        form.method ="post";
        form.name = '_excelDownloadForm';
        form.target = "_excelDownload";
        form.action = action;
        frame.appendChild(form);
        form.submit();
    }
    return true;
}

Ariel.helper.postUpload = function(){
    
}