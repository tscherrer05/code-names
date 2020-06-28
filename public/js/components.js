'use strict';

// Le système doit :
// OK Avoir des composants qui s'initialisent au chargement de la page
// - Avoir des composants qui réagissent à des évènements (utilisateurs ou évènements)
// OK Réagir à la réception de messages depuis le serveur
// OK Pouvoir se connecter par web socket à un serveur
// OK Pouvoir envoyer des messages au serveur

// WebSocket connection
const webSockUrl = 'ws://localhost:8080';
const conn = new WebSocket(webSockUrl);

// WebSocket events
conn.onopen = (e) => {
    console.log("Connection established!");
};

conn.onmessage = (e) => {
    if (e === null || e === undefined) {
        return;
    }

    const result = JSON.parse(e.data);

    switch (result.action) {
        case 'hasVoted':
            putVoteOnCard(result.x, result.y, result.playerKey);
            break;
        case 'cardReturned':
            returnCard(result.x, result.y, result.color);
            resetVotes();
        case null:
        case undefined:
        default:
            break;
    }
};

const RealTime = {
    send: (obj) => {
        console.log('Send to server !');
        conn.send(JSON.stringify(obj));
    }
}

const e = React.createElement;

class Board extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            gameKey:            props.gameKey,
            playerKey:          props.playerKey,
            cards:              props.cards,
            currentPlayerName:  'Biche'
        };
    }

    componentDidMount() {
        // TODO : Inscription à RealTime
        // RealTime.subscribe('hasVoted', )

    }

    componentWillUnmount() {
        // TODO : 
    }

    createCardComponent(c, gameKey, playerKey) {
        return e(Card, {
            key:    c.x+'-'+c.y,
            name:   c.word,
            color:  c.color,
            x:      c.x,
            y:      c.y,
            gameKey: gameKey,
            playerKey: playerKey
        });
    }

    render() {
        // TODO : display cards
        return e(
            'div',
            {
            },
            [
                e('div', 
                {
                    key: 'cn-cards-row',
                    id:"cn-cards-row",
                    className:"row"
                },
                this.state.cards.map(c => this.createCardComponent(c, this.state.gameKey, this.state.playerKey))
            )
            ]
        );
    }
}

class Card extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            gameKey:            props.gameKey,
            playerKey:          props.playerKey,
            word:               props.name,
            currentPlayerName:  props.currentPlayerName,
            x:                  props.x,
            y:                  props.y,
            color:              props.color,
            voted:              props.voted,
            revealed:           props.revealed,
        };
    }

    vote = () => {
        // Envoyer un message au composant de web socket
        RealTime.send({
            action: 'vote',
            parameters: {
                x:          this.state.x,
                y:          this.state.y,
                gameKey:    this.state.gameKey,
                playerKey:  this.state.playerKey
            }
        });
    }

    imgAttributes = () => {
        const attr = {
            key:        `${this.state.x}${this.state.y}`,
            onClick:    () => this.vote(),
            className:  'cn-card revealed',
        };
        if(this.state.revealed) {
            switch(this.state.color) {
                case 0:
                    attr['src'] = 'images/white.png';
                    break;
                case 1:
                    attr['src'] = 'images/blue1.png';
                    break;
                case 2:
                    attr['src'] = 'images/red0.png';
                    break;
                case 3:
                    attr['src'] = 'images/black.png';
                    break;
            }
        }
        else {
            attr['src'] = 'images/card.png';
        }

        return attr;
    };

    render() {
        return e('div', 
        {
            key: `card-container-${this.state.x}${this.state.y}`,
            id: `cn-card-${this.state.x}-${this.state.y}`,
            className: 'cn-card-container',
        },
        [
            e(
                'img',
                this.imgAttributes()
            ),
            e(
                'div',
                {
                    key: 'cn-card-text',
                    className: 'cn-card-text'
                },
                this.state.word
            )
        ]);
    }
}

$(document).ready(() => {
    const domContainer = document.querySelector('#board');
    const gameKey = document.querySelector('#gameKey').dataset.value;
    const playerKey = document.querySelector('#playerKey').dataset.value;

    $.get('/cards?gameKey=' + gameKey, data => {
        // TODO : Gestion d'erreur
        ReactDOM.render(e(Board, { gameKey: gameKey, playerKey: playerKey, cards: data }), domContainer);
    });
});

