$(function () {
    im.init();

    $(".sub_but").click(function () {
        im.sendMsg();
    });

    $("#message").keydown(function (event) {
        var e = window.event || event;
        var k = e.keyCode || e.which || e.charCode;
        //按下ctrl+enter发送消息
        if ((event.ctrlKey && (k == 13 || k == 10) )) {
            im.sendMsg();
        }
    });
});

var config = {
    server: 'ws://160.19.51.200:9501'
};

var im = {
    data: {
        wsServer: null,
        info: {}
    },
    init: function () {
        this.data.wsServer = new WebSocket(config.server);
        this.open();
        this.messages();
        this.close();
        this.error();
    },
    open: function () {
        this.data.wsServer.onopen = function (evt) {
            im.notice('连接成功');
            console.log(evt);
        }
    },
    messages: function () {
        this.data.wsServer.onmessage = function (evt) {
            var data = jQuery.parseJSON(evt.data);
            console.log(data);
            switch (data.type) {
                case 'open':
                    im.appendUser(data.user.name, data.user.avatar, data.user.fd);
                    im.notice(data.message);
                    break;
                case 'close':
                    im.removeUser(data.user.fd);
                    im.notice(data.message);
                    break;
                case 'openSuccess':
                    im.data.info = data.user;
                    im.showAllUser(data.all);

                    break;
                case 'message':
                    im.newMessage(data);
                    break;
            }
        };
    },
    close: function () {
        this.data.wsServer.onclose = function (evt) {
            im.notice('不妙，链接断开了');
            this.layerErrorMsg('不妙，链接断开了');
        }
    },
    error: function () {
        this.data.wsServer.onerror = function (evt, e) {
            console.log('Error occured: ' + evt.data);
        };
    },
    removeUser: function (fd) {
        $(".fd-" + fd).remove();
    },
    showAllUser: function (users) {
        for (i in users) {
            this.appendUser(users[i].name, users[i].avatar, users[i].fd);
        }
    },
    sendMsg: function () {
        var msg = $("#message").val();
        if ($.trim(msg) == '') {
            this.layerErrorMsg('请输入消息内容');
            return false;
        }
        this.data.wsServer.send(msg);

        var htmlData = '<div class="msg_item fn-clear">'
            + '   <div class="uface"><img src="' + this.data.info.avatar + '" width="40" height="40"  alt=""/></div>'
            + '   <div class="item_right">'
            + '     <div class="msg own">' + msg + '</div>'
            + '     <div class="name_time">' + this.data.info.name + ' · ' + this.datetime() + '</div>'
            + '   </div>'
            + '</div>';
        $("#message_box").append(htmlData);
        $('#message_box').scrollTop($("#message_box")[0].scrollHeight + 20);
        $("#message").val('');
    },
    newMessage: function (data) {
        this.appendUser(data.user.name, data.user.avatar, data.user.fd);

        var html = '<div class="msg_item fn-clear">'
            + '<div class="uface"><img src="' + data.user.avatar + '" width="40" height="40" alt=""/></div>'
            + '<div class="item_right">'
            + '<div class="msg">' + data.message + '</div>'
            + '<div class="name_time">' + data.user.name + ' · ' + data.datetime + '</div>'
            + '</div>'
            + '</div>';

        $('.message_box').append(html);
        this.scrollBottom();
    },
    scrollBottom: function () {
        $('#message_box').scrollTop($("#message_box")[0].scrollHeight + 20);
    },
    notice: function (msg) {
        var html = '<div style="text-align: center;">' + msg + '</div>';
        $('#message_box').append(html);

        this.scrollBottom();
    },
    appendUser: function (name, avatar, fd) {
        if ($(".fd-" + fd).length > 0) {
            return true;
        }

        var html = '<li class="fn-clear fd-' + fd + '" data-id="' + fd + '">'
            + '<span><img src="' + avatar + '" width="30" height="30"  alt=""/></span>'
            + '<em>' + name + '</em><small class="online" title="在线"></small></li>';
        $(".user_list").append(html);
        $('.user_list').scrollTop($('.user_list')[0].scrollHeight);
    },
    layerSuccessMsg: function (msg) {
        layer.msg(msg, {time: 1000, icon: 6});
    },
    layerErrorMsg: function (msg) {
        layer.msg(msg, {time: 1000, icon: 5});
    },
    datetime: function getNowFormatDate() {
        var date = new Date();
        var seperator1 = "-";
        var seperator2 = ":";
        var month = date.getMonth() + 1;
        var strDate = date.getDate();
        if (month >= 1 && month <= 9) {
            month = "0" + month;
        }
        if (strDate >= 0 && strDate <= 9) {
            strDate = "0" + strDate;
        }
        var currentdate = date.getFullYear() + seperator1 + month + seperator1 + strDate
            + " " + date.getHours() + seperator2 + date.getMinutes()
            + seperator2 + date.getSeconds();
        return currentdate;
    }
};