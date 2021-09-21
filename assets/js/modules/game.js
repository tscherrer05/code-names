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
    if(eventData == null || eventData.x == null || eventData.y == null) {
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
 * @param {string} eventData.playerName
 * @param {string} eventData.word
 */
const returnCard = (state, eventData) => {
    return {
        events: [
            ...(state.events || []),
            { key: Date.now(), text: "L'équipe " + Teams.stringFromInt(eventData.team)["fr"] + " a retourné la carte '" + eventData.word + "'" }
        ],
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
 * @param {Readonly<S>} state
 * @param {{}} state.players
 * @param {{}} state.currentVotes
 * @param {{}} state.currentTeam
 * @param {string[]} state.remainingVotes
 * @param {{playerKey: string, playerName: string, playerTeam: null, playerRole: null}} eventData
 * @param {string} eventData.playerName
 * @param {string} eventData.playerKey
 * @param {number} eventData.playerRole
 * @param {number} eventData.playerTeam
 * @returns  {{players, remainingVotes, currentVotes}}
 */
const addNewPlayer = (state, eventData) => {
    const isValid = eventData.playerRole && eventData.playerTeam;
    const hasVoted = (state.currentVotes?.hasOwnProperty(eventData.playerKey) || false);
    const isMaster = eventData.playerRole === Roles.Master;
    const isOppositeTeam = eventData.playerTeam !== state.currentTeam;
    const players = isValid
        ? {
            ...state.players || {},
            [eventData.playerKey]: {name:eventData.playerName, role:eventData.playerRole, team:eventData.playerTeam}
        }
        : state.players || {};

    return {
        players: players,
        remainingVotes:  hasVoted || isMaster || isOppositeTeam
                            ? state.remainingVotes 
                            : [...new Set(state.remainingVotes).add(eventData.playerKey)],
        currentVotes: state.currentVotes || {}
    };
}

/**
 * Removes a player from the game.
 * @param state
 * @param eventData
 * @returns {{players}}
 */
const removePlayer = (state, eventData) => {
    return {
        players: Object.fromEntries(Object.entries(state.players).filter(([key, value]) => key !== eventData.playerKey) ),
        remainingVotes: state.remainingVotes.filter(k => k !== eventData.playerKey),
        currentVotes: Object.fromEntries(Object.entries(state.currentVotes).filter(([key, value]) => key !== eventData.playerKey) )
    }
}

/**
 * Passes turn to the next team.
 * @param {*} state
 * @param eventData
 */
const passTurn = (state, eventData) => {
    if(!eventData || !eventData.remainingVotes)
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

export {vote, returnCard, addNewPlayer, removePlayer, passTurn}