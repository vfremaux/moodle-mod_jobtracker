
function ajax_send_select_status(wwwroot, jobid, selectobj) {
    // JQuery send new status
    url = wwwroot+'/mod/jobtracker/ajax/service.php?what=updatestatus&jobid='+jobid+'&status='+selectobj.options[selectobj.selectedIndex].value;

    $.get(url, function(data, status){
        selectorid = '#status-select-'+jobid;
        $(selectorid).html(data);
    });
    
}

function ajax_send_status(wwwroot, jobid, statusid, statuscode) {
    // JQuery send new status
    url = wwwroot+'/mod/jobtracker/ajax/service.php?what=updatelist&schanged'+jobid+'=1&status'+jobid+'='+statusid;

    $.get(url, function(data, status){
    });
    
    // Local change status
    pickerid = '#status-picker-current-'+jobid;
    newclass = 'status-'+statuscode;
    $(pickerid).attr('class', newclass);
}

function togglehistory() {
    historydiv = document.getElementById("jobhistory");
    historylink = document.getElementById("togglehistorylink");
    if (historydiv.className == "visiblediv") {
        historydiv.className = "hiddendiv";
        historylink.innerText = showhistory;
    } else {
        historydiv.className = "visiblediv";
        historylink.innerText = hidehistory;
    }
}


function toggleccs() {
    ccsdiv = document.getElementById('jobccs');
    ccslink = document.getElementById('toggleccslink');
    if (ccsdiv.className == 'visiblediv') {
        ccsdiv.className = 'hiddendiv';
        ccslink.innerText = showccs;
    } else {
        ccsdiv.className = 'visiblediv';
        ccslink.innerText = hideccs;
    }
}


function togglecomments() {
    commentdiv = document.getElementById('jobcomments');
    commentlink = document.getElementById('togglecommentlink');
    if (commentdiv.className == 'visiblediv comments') {
        commentdiv.className = 'hiddendiv comments';
        commentlink.innerText = showcomments;
    } else {
        commentdiv.className = 'visiblediv comments';
        commentlink.innerText = hidecomments;
    }
}


function toggletrackvisibility(userid, upurl, downurl) {

    $ctrl = $('#trackctl_'+userid);

    if ($ctrl.attr('class') == 'tracks-hidden') {
        $ctrl.attr('src', downurl);
        $ctrl.removeClass('tracks-hidden');
        $('.track-'+userid).removeClass('hidden');
    } else {
        $ctrl.attr('src', upurl);
        $('.track-'+userid).addClass('hidden');
        $ctrl.addClass('tracks-hidden');
    }
}
