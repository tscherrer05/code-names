import {Game} from './game'

// Le système doit :
// OK Avoir des composants qui s'initialisent au chargement de la page
// - Avoir des composants qui réagissent à des évènements (utilisateurs ou DOM)
// OK Réagir à la réception de messages depuis le serveur
// OK Pouvoir se connecter par web socket à un serveur
// OK Pouvoir envoyer des messages au serveur
// - Gérer les erreurs
// - Avoir des composants testables

// TODO : où gérer la connection ws ?
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
          // Publier un évènement sur ce topic avec ces données. Les composants DOM inscrits y réagiront.
          PubSub.publish('hasVoted', {
            x: result.x,
            y: result.y,
            playerKey: result.playerKey,
            playerName: result.playerName
          })
          break;
          // Publier un évènement sur ce topic avec ces données. Les composants DOM inscrits y réagiront.
        case 'cardReturned':
          PubSub.publish('cardReturned', {
            x: result.x,
            y: result.y,
            color: result.color
          })
        case null:
        case undefined:
        default:
            break;
    }
};

$(document).ready(() => {
  const domContainer  = document.querySelector('#game');
  const gameKey = document.querySelector('#gameKey').dataset.value;
  const playerKey = document.querySelector('#playerKey').dataset.value;

  // Rendu du jeu
  ReactDOM.render(<Game gameKey={gameKey} playerKey={playerKey} />, domContainer)

  // Evènements
  PubSub.subscribe('vote', (evt, data) => {
    conn.send(JSON.stringify(
      {
        action: evt,
        parameters: data
      }
    ))
  })
})