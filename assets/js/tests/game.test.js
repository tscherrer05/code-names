import { vote, returnCard, addNewPlayer, passTurn } from '../modules/game'
import {Roles} from '../roles'
import {Teams} from '../teams'

const cards = [
    {
        "color": 1,
        "returned": false,
        "word": "orange",
        "x": 0,
        "y": 0,
        "voters": []
    },
    {
        "color": 2,
        "returned": false,
        "word": "chimpanzé",
        "x": 0,
        "y": 1,
        "voters": []
    },
    {
        "color": 2,
        "returned": true,
        "word": "orteil",
        "x": 0,
        "y": 2,
        "voters": []
    },
    {
        "color": 0,
        "returned": true,
        "word": "courgette",
        "x": 0,
        "y": 3,
        "voters": []
    },
    {
        "color": 0,
        "returned": true,
        "word": "potiron",
        "x": 0,
        "y": 4,
        "voters": []
    },
    {
        "color": 1,
        "returned": false,
        "word": "courgette",
        "x": 1,
        "y": 0,
        "voters": []
    },
    {
        "color": 2,
        "returned": true,
        "word": "télévision",
        "x": 1,
        "y": 1,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "patate",
        "x": 1,
        "y": 2,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "chat",
        "x": 1,
        "y": 3,
        "voters": []
    },
    {
        "color": 2,
        "returned": true,
        "word": "courgette",
        "x": 1,
        "y": 4,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "peinture",
        "x": 2,
        "y": 0,
        "voters": []
    },
    {
        "color": 3,
        "returned": true,
        "word": "fraise",
        "x": 2,
        "y": 1,
        "voters": []
    },
    {
        "color": 2,
        "returned": true,
        "word": "armoire",
        "x": 2,
        "y": 2,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "cortège",
        "x": 2,
        "y": 3,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "multiplication",
        "x": 2,
        "y": 4,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "poubelle",
        "x": 3,
        "y": 0,
        "voters": []
    },
    {
        "color": 2,
        "returned": true,
        "word": "portable",
        "x": 3,
        "y": 1,
        "voters": []
    },
    {
        "color": 0,
        "returned": true,
        "word": "maximum",
        "x": 3,
        "y": 2,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "poterie",
        "x": 3,
        "y": 3,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "rome",
        "x": 3,
        "y": 4,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "Potentiel",
        "x": 4,
        "y": 0,
        "voters": []
    },
    {
        "color": 2,
        "returned": true,
        "word": "Serviette",
        "x": 4,
        "y": 1,
        "voters": []
    },
    {
        "color": 3,
        "returned": true,
        "word": "Pied",
        "x": 4,
        "y": 2,
        "voters": []
    },
    {
        "color": 0,
        "returned": true,
        "word": "Feuille",
        "x": 4,
        "y": 3,
        "voters": []
    },
    {
        "color": 1,
        "returned": true,
        "word": "Voiture",
        "x": 4,
        "y": 4,
        "voters": []
    }
];

const playerOneKey      = 'bc3932e8-4318-46ec-ad7c-76cc7c3e6596'
const playerTwoKey      = 'eb405817-9987-46f9-bf32-e6af01865ee6'
const playerThreeKey    = 'eb405817-9987-46f9-bf32-e6af01865sso'

const baseState = {
    gameKey: '2c3f62bb-1532-4840-a71a-65f3b1f25c0a',
    playerKey: playerOneKey,
}

const voteEventData =  {
    playerKey: playerTwoKey,
    x: 2,
    y: 3
}

const returnEventData = {
    x: 2,
    y: 3
}

const addPlayerEvent = {
    playerKey: playerThreeKey,
    playerName: 'PLAYER_TEST'
}

test('vote with null data', () => {
    const votes = {
        currentVotes: {},
        remainingVotes: [2, 3]
    }
    const state = { 
        ...baseState,
        ...votes,
        players: {
            [playerOneKey]: 'Chuck',
            [playerTwoKey]: 'Pat'
        }
    }

    var result = vote(state, null)

    expect(result).toStrictEqual(votes)
})

test('vote with incorrect state', () => {
    const state = {
        ...baseState,
        currentVotes: {},
        remainingVotes: null,
        players: {
            [playerOneKey]: 'Chuck',
            [playerTwoKey]: 'Pat'
        }
    }

    var result = vote(state, voteEventData)

    expect(result).toStrictEqual({
        currentVotes: {},
        remainingVotes: []
    })
})


test('vote with incorrect data', () => {
    const votes = {
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey]
    }
    const state = {
        ...baseState,
        ...votes,
        players: {
            [playerOneKey]: 'Chuck',
            [playerTwoKey]: 'Pat'
        }
    }

    const eventData = {
        playerKey: playerTwoKey,
        playerName: 'test'
    }

    var result = vote(state, eventData)

    expect(result).toStrictEqual(votes)
})

test('player first vote', () => {
    const state = {
        ...baseState,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        players: {
            [playerOneKey]: 'Chuck',
            [playerTwoKey]: 'Pat'
        }
    }

    var result = vote(state, voteEventData)

    expect(result).toStrictEqual({
        currentVotes: {[playerTwoKey]: 23},
        remainingVotes: [playerOneKey]
    });
})

test('player vote for card nominal', () => {

    const state = {
        ...baseState,
        currentVotes: {[playerOneKey]: 23},
        remainingVotes: [playerTwoKey],
        players: {
            [playerOneKey]: 'Chuck',
            [playerTwoKey]: 'Pat'
        }
    }

    var result = vote(state, voteEventData)

    expect(result).toStrictEqual({
        currentVotes: {[playerOneKey]: 23, [playerTwoKey]: 23},
        remainingVotes: []
    })
});

test('player vote for other card nominal', () => {

    const state = {
        ...baseState,
        currentVotes: {[playerOneKey]: 23, [playerTwoKey]: 12},
        remainingVotes: [],
        players: {
            [playerOneKey]: 'Chuck',
            [playerTwoKey]: 'Pat'
        }
    }

    var result = vote(state, voteEventData)

    expect(result).toStrictEqual({
        currentVotes:  {[playerOneKey]: 23, [playerTwoKey]: 23},
        remainingVotes: []
    })
})


test('return a card nominal', () => {

    const state = {
        ...baseState,
        cards: [{x:2, y: 3, returned: false}],
        currentVotes: {[playerOneKey]: 23, [playerTwoKey]: 23},
        remainingVotes: [],
        players: {
            [playerOneKey]: 'Chuck',
            [playerTwoKey]: 'Pat'
        }
    }

    var result = returnCard(state, returnEventData);

    expect(result).toStrictEqual({
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        cards: [{x:2, y:3, returned: true}]
    })
})


test('player joined first', () => {
    const state = {
        ...baseState,
        remainingVotes: []
    }

    var result = addNewPlayer(state, addPlayerEvent)

    expect(result).toStrictEqual({
        players: [{key: playerThreeKey, name: addPlayerEvent.playerName}],
        remainingVotes: [playerThreeKey]
    })
})

test('player joined nominal', () => {
    const state = {
        ...baseState,
        players: [{key: playerOneKey, name: 'PLAYER_TEST'+playerOneKey}],
        remainingVotes: [playerOneKey]
    }

    var result = addNewPlayer(state, addPlayerEvent)

    expect(result).toStrictEqual({
        players: [
            {key: playerOneKey, name: 'PLAYER_TEST'+playerOneKey},
            {key: playerThreeKey, name: 'PLAYER_TEST'}
        ],
        remainingVotes: [playerOneKey, playerThreeKey]
    })
})

test('player joined invalid state', () => {
    const state = {
        ...baseState,
        players: null,
        remainingVotes: null
    }

    var result = addNewPlayer(state, addPlayerEvent)

    expect(result).toStrictEqual({
        players: [{key: playerThreeKey, name: 'PLAYER_TEST'}],
        remainingVotes: [playerThreeKey]
    })
})


test('pass turn with invalid state', () => {
    expect(passTurn({
        ...baseState,
        currentVotes: null,
        currentTeam: null
    }, {
        remainingVotes: null
    }))
    .toStrictEqual({})
})

test('passTurn with no votes', () => {

    expect(
        passTurn(
        {
            ...baseState,
            canPassTurn: true,
            remainingVotes: [],
            currentTeam: 1
        },
        {
            remainingVotes: [playerOneKey, playerTwoKey]
        })
    ).toStrictEqual({
        currentTeam: 2,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: false
    })

    expect(passTurn(
        {
            ...baseState,
            canPassTurn: false,
            remainingVotes: [],
            currentTeam: 2
        },
        {
            remainingVotes: [playerOneKey, playerTwoKey],
        })
    )
    .toStrictEqual({
        currentTeam: 1,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: false
    })

})

test('passTurn with votes', () => {

    expect(
        passTurn(
        {
            ...baseState,
            players: [],
            currentVotes: {[playerOneKey]: 33},
            currentTeam: 1
        },
        {
            remainingVotes: [playerOneKey, playerTwoKey],
        })
    )
    .toStrictEqual(
    {
        currentTeam: 2,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: false
    })

})

test('pass turn to master spy team', () => {

    expect(passTurn({
        ...baseState,
        role: Roles.Master,
        playerTeam: Teams.Blue,
        currentTeam: Teams.Red,
        canPassTurn: false
    }, {
        remainingVotes: [playerOneKey, playerTwoKey]
    })).toStrictEqual({
        currentTeam: Teams.Blue,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: true
    })

})

test('pass turn to master spy adverse team', () => {

    expect(passTurn({
        ...baseState,
        role: Roles.Master,
        playerTeam: Teams.Red,
        currentTeam: Teams.Red,
        canPassTurn: true
    }, {
        remainingVotes: [playerOneKey, playerTwoKey]
    })).toStrictEqual({
        currentTeam: Teams.Blue,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: false
    })

})