import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import { Events } from './events';
import Game from './game'

// TODO : où gérer la connection ws ?
// TODO : config env
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
        case Events.HAS_VOTED:
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
        case Events.CARD_RETURNED:
          PubSub.publish(Events.CARD_RETURNED, {
            x: result.x,
            y: result.y,
            color: result.color
          })
          break
        case Events.TURN_PASSED:
          PubSub.publish(Events.TURN_PASSED, {
            team: result.team,
            remainingVotes: result.remainingVotes,
          })
          break
        case Events.PLAYER_JOINED:
          PubSub.publish(Events.PLAYER_JOINED, {
            playerKey: result.playerKey,
            playerName: result.playerName,
            playerRole: result.playerRole,
            playerTeam: result.playerTeam
          })
          break
        case Events.GAME_HAS_RESET:
          location.reload()
          break
        case Events.GAME_IS_EMPTIED:
          location.href = result.redirectUrl
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

  PubSub.subscribe(Events.RESET_GAME, (evt, data) => {
    send(evt, data)
  })

  PubSub.subscribe(Events.EMPTY_GAME, (evt, data) => {
    send(evt, data)
  })

  setInterval(() => {send('heartBeat', {})}, 30000)

})