import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import { Events } from './events';
import Game from './game'

// Le système doit :
// OK Avoir des composants qui s'initialisent au chargement de la page
// OK Avoir des composants qui réagissent à des évènements (utilisateurs ou DOM)
// OK Réagir à la réception de messages depuis le serveur
// OK Pouvoir se connecter par web socket à un serveur
// OK Pouvoir envoyer des messages au serveur
// OK Gérer les erreurs
// OK Avoir des composants testables

// TODO : où gérer la connection ws ?
// WebSocket connection
const webSockUrl = 'ws://localhost:8080'
const conn = new WebSocket(webSockUrl)
const send = (evt, data) => conn.send(JSON.stringify(
  {
    action: evt,
    parameters: data
  }
))

// WebSocket events
conn.onopen = (e) => {
    const gameKey = document.querySelector('#gameKey').dataset.value
    const playerKey = document.querySelector('#playerKey').dataset.value
    
    send(Events.PLAYER_CONNECTED, {
      gameKey: gameKey,
      playerKey: playerKey
    })
}

conn.onmessage = (e) => {
    if (e === null || e === undefined) {
        return
    }

    const result = JSON.parse(e.data)

    switch (result.action) {
        case 'hasVoted':
          if('error' in result) {
            PubSub.publish(Events.HAS_VOTED, {
              error: result.error,
              message: result.message,
            })
          } else {
            PubSub.publish(Events.HAS_VOTED, {
              x: result.x,
              y: result.y,
              playerKey: result.playerKey,
              playerName: result.playerName
            })
          }
          break
        case 'cardReturned':
          PubSub.publish(Events.CARD_RETURNED, {
            x: result.x,
            y: result.y,
            color: result.color
          })
          break
        case Events.TURN_PASSED:
          PubSub.publish(Events.TURN_PASSED, {
            team: result.team,
            remainingVotes: result.remainingVotes
          })
          break
        case Events.PLAYER_JOINED:
          PubSub.publish(Events.PLAYER_JOINED, {
            playerKey: result.playerKey,
            playerName: result.playerName,
            playerRole: result.playerRole,
            playerTeam: result.playerTeam
          })
        case null:
        case undefined:
        default:
            break
    }
} 

$(document).ready(() => {
  const domContainer  = document.querySelector('#game')
  const gameKey = document.querySelector('#gameKey').dataset.value
  const playerKey = document.querySelector('#playerKey').dataset.value

  // DOM rendering
  ReactDOM.render(<Game gameKey={gameKey} playerKey={playerKey} />, domContainer)

  // Events subscriptions
  PubSub.subscribe(Events.VOTE, (evt, data) => {
    send(evt, data)
  })

  PubSub.subscribe(Events.PASS_TURN, (evt, data) => {
    send(evt, data)
  })
})