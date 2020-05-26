$(document).ready(function () {
    // GAME SETUP
    const gameKey = $('#gameKey').data('value');
    const playerKey = $('#playerKey').data('value');
    const playerName = $('#current-player').data('value');

    function returnCard(i, j, color) {
        var img = $(`#cn-card-${i}-${j} img`);
        var imageName = color == 1 ? "blue0" : "red0";
        img.toggleClass('revealed');
        img.fadeOut(300, function () {
            img.attr("src", "images/" + imageName + ".png");
        }).fadeIn(600);
        img.parent().find(".cn-card-text").fadeOut(300);
    }

    function putVoteOnCard(x, y, playerKey) {
        var img = $(`#cn-card-${x}-${y} img`);
        $("#vote-tag-" + playerKey).appendTo(img.parent().find(".cn-card-votes"));
    }

    // WebSocket connection
    const webSockUrl = 'ws://localhost:8080';
    var conn = new WebSocket(webSockUrl);

    // WebSocket events
    conn.onopen = function (e) {
        console.log("Connection established!");   
    };

    conn.onmessage = function (e) {
        if(e === null || e === undefined) 
        {
            return;
        }

        const result = JSON.parse(e.data);

        switch (result.action) {
            case "vote":
                putVoteOnCard(result.x, result.y, result.playerKey);
                break;
            case null:
            case undefined:
            default:
                break;
        }
    };

    $("img.cn-card, .cn-card-text").click(function () {
        let card = $(this).parent();
        // TODO : use data attributes
        var i = parseInt(card.attr('id').substring(8, 9));
        var j = parseInt(card.attr('id').substring(10, 11));

        var message = {
            action: "vote",
            parameters: {
                x: i,
                y: j,
                gameKey: gameKey,
                playerKey: playerKey
            }
        };
        conn.send(JSON.stringify(message));

        putVoteOnCard(i, j, playerKey);
    });

    // Who starts?
    // if == 1, the red starts, if == 0, then blue starts
    // first_team = Math.floor(Math.random() * Math.floor(2));
    // if (first_team == 1) {
    //     $("body").css("border", "5px solid #8a1a18")
    //     $(".modal-footer").html("<button type='button' class='btn btn-danger' data-dismiss='modal'>C'est bon, go ! (les rouges commencent)</button>");
    // } else {
    //     $("body").css("border", "5px solid #005e9b")
    //     $(".modal-footer").html("<button type='button' class='btn btn-primary' data-dismiss='modal'>C'est bon, go ! (les bleus commencent)</button>");
    // }


    // Init grid colors
    // var grid_colors = [];
    // for (var i = 0; i < 8 + first_team; i++) grid_colors.push("red");
    // for (var i = 0; i < 9 - first_team; i++) grid_colors.push("blue");
    // for (var i = 0; i < 7; i++) grid_colors.push("white");
    // grid_colors.push("black");
    // shuffle
    // for (var j, x, i = grid_colors.length; i; j = parseInt(Math.random() * i), x = grid_colors[--i], grid_colors[i] = grid_colors[j], grid_colors[j] = x);

    // Init words
    // Load dictionnary
    // common_names_len = common_names.length;
    // for (var i = 0; i < 5; i++) {
    //     $("#cn-cards").append("<div class='row' id='cn-cards-row-" + i.toString() + "'></div>");
    //     $("#cn-schema").append("<div class='row'></div>");
    //     for (var j = 0; j < 5; j++) {
    //         $("#cn-cards-row-" + i.toString()).append("<div class='cn-card-container' id='cn-card-" + i.toString() + "-" + j.toString() + "'><img class='cn-card' src='images/card.png'></div>");
    //         $("#cn-schema").find(".row").last().append("<img class='card cn-schema-img' src='images/schema-" + grid_colors[5 * i + j] + ".png'>");

    //         idx = Math.floor(Math.random() * common_names_len);
    //         $("#cn-card-" + i.toString() + "-" + j.toString()).append("<div class='cn-card-text'>" + common_names[idx].toUpperCase() + "</div>");
    //         common_names.splice(idx, 1);
    //         common_names_len--;
    //     }
    // }

    // Show modal
    // $('#cn-explain').modal();
    // $("#cn-show-schema").click(function () {
    //     $('#cn-explain').modal();
    // });
});
