import { Roles } from "../roles"
import { Teams } from "../teams"


/**
 * 
 * @param {currentVotes, remainingVotes, cards} state 
 * @param {x, y, playerKey} eventData 
 */
const vote = (state, eventData) => {
    if(eventData == null || eventData.x == null || eventData.y == null) {
        return {
            currentVotes: state.currentVotes,
            remainingVotes: state.remainingVotes
        }
    }
    const key = parseInt("" + eventData.x + eventData.y)

    return {
        currentVotes: state.remainingVotes == null 
        ? state.currentVotes
        : { 
            ...state.currentVotes,
            [eventData.playerKey]: key       
        },
        remainingVotes: state.remainingVotes?.filter(v => { if(v !== eventData.playerKey) return v }) || []
    }
}

/**
 * 
 * @param {*} state 
 * @param {*} eventData 
 */
const returnCard = (state, eventData) => {
    return {
        currentVotes: {},
        remainingVotes: Object.entries(state.currentVotes).map(v => {
            return v[0]
        }),
        cards: state.cards.map(c => {
            if(c.x === eventData.x && c.y === eventData.y) {
                c.returned = true
            }
            return c
        })
    }
}

/**
 * 
 * @param {*} state 
 * @param {*} eventData 
 */
const addNewPlayer = (state, eventData) => {
    return {
        players: [...state.players || [], {key: eventData.playerKey, name: eventData.playerName}],
        remainingVotes: [...state.remainingVotes || [], eventData.playerKey]
    }
}


/**
 * 
 * @param {*} state 
 */
const passTurn = (state, eventData) => {
    if(!eventData || !eventData.remainingVotes)
        return {}
    const newTeam = state.currentTeam === Teams.Blue ? Teams.Red : Teams.Blue
    return {
        currentTeam: newTeam,
        currentVotes: {},
        remainingVotes: eventData.remainingVotes,
        canPassTurn: state.role === Roles.Master && state.playerTeam === newTeam
    }
}

export {vote, returnCard, addNewPlayer, passTurn}