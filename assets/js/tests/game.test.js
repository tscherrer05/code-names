import { vote, returnCard } from '../modules/game';


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

const playerOneKey = 'bc3932e8-4318-46ec-ad7c-76cc7c3e6596'
const playerTwoKey = 'eb405817-9987-46f9-bf32-e6af01865ee6'

const baseState = {
    gameKey: '2c3f62bb-1532-4840-a71a-65f3b1f25c0a',
    playerKey: playerOneKey,
    players: {
        [playerOneKey]: 'Chuck',
        [playerTwoKey]: 'Pat'
    }
}

const voteEventData =  {
    playerKey: playerTwoKey,
    x: 2,
    y: 3
}

const returnEventData = {

}

test('vote with null data', () => {
    const votes = {
        currentVotes: {},
        remainingVotes: [2, 3]
    }
    const state = { 
        ...baseState,
        ...votes
    }

    var result = vote(state, null)

    expect(result).toStrictEqual(votes)
})

test('vote with incorrect state', () => {
    const state = {
        ...baseState,
        currentVotes: {},
        remainingVotes: null
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
        ...votes
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
        remainingVotes: [playerOneKey, playerTwoKey]
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
        remainingVotes: [playerTwoKey]
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
        remainingVotes: []
    }

    var result = vote(state, voteEventData)

    expect(result).toStrictEqual({
        currentVotes:  {[playerOneKey]: 23, [playerTwoKey]: 23},
        remainingVotes: []
    })
})