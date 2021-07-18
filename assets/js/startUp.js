import React, { Component } from 'react'
import ReactDOM from 'react-dom'
import regeneratorRuntime from "regenerator-runtime" // Needed to use 'async' keyword
import { WebSocketServer } from './WebSocketServer'
import { Events } from './events'
import Game from './game'
import processRealTimeMessageEvent from './processRealTimeMessageEvent'
import PubSub from 'pubsub-js'


/**
 * Initializes all components of the game
 * @param {HTMLElement} domContainer Element in which the game will be rendered
 * @param {Guid} gameKey Guid of the game to render
 * @param {Guid} playerKey Guid of the current player
 * @param {object?} callback Called after the game is rendered
 */
export async function StartUp(domContainer, gameKey, playerKey, callback = null) {

    const realTimeServer = new WebSocketServer(process.env.WEB_SOCKET_URL)

    const send = (evt, data) => realTimeServer.send(evt, data)

    realTimeServer.onopen((e) => {
        send(Events.PLAYER_CONNECTED, {
            gameKey: gameKey,
            playerKey: playerKey
        })
    })

    realTimeServer.onmessage(processRealTimeMessageEvent)

    // DOM rendering
    ReactDOM.render(<Game gameKey={gameKey} playerKey={playerKey} />, domContainer, callback)

    // Events subscriptions
   const evSubs =  [
       Events.VOTE,
       Events.PASS_TURN,
       Events.RESET_GAME,
       Events.EMPTY_GAME,
       Events.LEAVE_GAME
   ]
    for (const e of evSubs) {
        PubSub.subscribe(e, (evt, data) => send(evt, data))
    }

    setInterval(() => {
        send('heartBeat', {})
    }, 30000)

}