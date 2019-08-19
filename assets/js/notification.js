(function( $ ) {
    let data_obj =  {   "content" : "Task #" + ajax_object.task_id + " - " + ajax_object.status,
                        "embeds": [
                            {

                                "fields": [
                                    {
                                        "name": "Callback action",
                                        "value": ajax_object.callback_action,
                                        "inline": true
                                    },
                                    {
                                        "name": "Status",
                                        "value": ajax_object.status,
                                        "inline": true
                                    },
                                    {
                                        "name": "Task url",
                                        "value": ajax_object.task_url,
                                        "inline": false
                                    }
                                ]
                            }
                        ]
                    };
    let ajax_url = ajax_object.webhook;

    $.ajax({
        type : 'POST',
        url : ajax_url,
        data: JSON.stringify( data_obj ),
        contentType: "application/json; charset=utf-8",
        dataType: "json",
    });

})( jQuery );