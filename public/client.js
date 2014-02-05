var chat = {};

chat.join = function(name) {
    var data = {
        'type': 'join',
        'name': name,
    };
    sock.send(JSON.stringify(data));
}

chat.talk = function(line) {
    var data = {
        'type': 'talk',
        'msg': line,
    };
    sock.send(JSON.stringify(data));
}

var chatui = {};
chatui.addLine = function(line) {
    $('#chatlog').append('<li>' + line + '</li>');
}

var sock = new ReconnectingWebSocket('ws://'+window.location.hostname+':8080/');

sock.onopen = function() {
    $('#connection-state').html('Connected').addClass('label-success').removeClass('label-danger');
    $('#buttons').html($('#join-tmpl').html());
};

sock.onmessage = function(e) {
    msg = JSON.parse(e.data);

    console.log(msg);

    if (msg.type == 'said') {
        chatui.addLine(msg.line);
    }
};

sock.onclose = function() {
    $('#connection-state').html('Disconnected').addClass('label-danger').removeClass('label-success');
    $('#buttons').html('');
};

$('#buttons').on('click', 'button.js-join', function() {
    var name = $('input.js-name', $('#buttons')).val();
    chat.join(name);
    $('#buttons').html($('#talk-tmpl').html());
    $('#buttons input').focus();
});

$('#buttons').on('click', 'button.js-say', function() {
    var line = $('input.js-line', $('#buttons')).val();
    if ( ! line) return;
    chat.talk(line);
});

$('#buttons').on('keypress', 'input', function (e) {
  if (e.which == 13)
      $('#buttons button').click();
});

