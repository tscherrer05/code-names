import { Events } from "./events"
import PubSub from 'pubsub-js'

const processRealTimeMessageEvent = (e) => {
    if (e === null || e === undefined) {
        return
    }

    const result = JSON.parse(e.data)

    switch (result.action) {
        case Events.HAS_VOTED:
            if ('error' in result) {
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
                color: result.color,
                team: result.team,
                word: result.word
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

export default processRealTimeMessageEvent