import { Roles } from "../roles.js"
import { Teams } from "../teams.js"


/**
 * Vote for a card
 * @param {Object} state 
 * @param {{}} state.currentVotes
 * @param {string[]} state.remainingVotes
 * @param {Object} eventData
 * @param {string} eventData.x
 * @param {string} eventData.y
 */
const vote = (state, eventData) => {
    if(eventData == null || eventData.x == null || eventData.y == null) {
        return {
            currentVotes: state.currentVotes,
            remainingVotes: state.remainingVotes
        }
    }
    const key = "" + eventData.x + eventData.y

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
 * Return a card and clears all its votes
 * @param {Object} state 
 * @param {{}} state.currentVotes
 * @param {string[]} state.remainingVotes
 * @param {Object} eventData
 * @param {string} eventData.x
 * @param {string} eventData.y 
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
 * Adds a player to the game in an idempotent way.
 * @param {Object} state 
 * @param {{}} state.players
 * @param {{}} state.currentVotes
 * @param {string[]} state.remainingVotes
 * @param {Object} eventData 
 * @param {string} eventData.playerName 
 * @param {string} eventData.playerKey
 * @param {number} eventData.playerRole
 * @param {number} eventData.playerTeam
 */
const addNewPlayer = (state, eventData) => {
    const hasVoted = (state.currentVotes?.hasOwnProperty(eventData.playerKey) || false)
    const isMaster = eventData.playerRole === Roles.Master
    const isOppositeTeam = eventData.playerTeam != state.currentTeam
    return {
        players: {...state.players || {}, [eventData.playerKey]: {name:eventData.playerName, role:eventData.playerRole, team:eventData.playerTeam}},
        remainingVotes:  hasVoted || isMaster || isOppositeTeam
                            ? state.remainingVotes 
                            : [...new Set(state.remainingVotes).add(eventData.playerKey)],
        currentVotes: state.currentVotes || {}
    }
}

/**
 * Passes turn to the next team.`
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
        canPassTurn: state.role === Roles.Master && state.playerTeam === newTeam, // TODO : refactor
        isMyTurn: state.playerTeam === newTeam
    }
}

export {vote, returnCard, addNewPlayer, passTurn}