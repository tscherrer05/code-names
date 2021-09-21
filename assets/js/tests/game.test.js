import { vote, returnCard, addNewPlayer, passTurn } from '../modules/game'
import {Roles} from '../roles'
import {Teams} from '../teams'

// DATA
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
    x: 0,
    y: 2
}

const returnEventData = {
    x: 2,
    y: 3,
    team: Teams.Red
}

const addPlayerEvent = {
    playerKey: playerThreeKey,
    playerName: 'PLAYER_TEST',
    playerRole: null,
    playerTeam: null
}



// VOTE

test('vote with null data', () => {
    const votes = {
        currentVotes: {},
        remainingVotes: [2, 3]
    }
    const state = { 
        ...baseState,
        ...votes,
        players: {
            [playerOneKey]: {name:'Chuck', role:Roles.Spy, team:Teams.Blue},
            [playerTwoKey]: {name:'Pat', role:Roles.Spy, team:Teams.Blue}
        }
    }

    const result = vote(state, null);

    expect(result).toStrictEqual(votes)
})

test('vote with incorrect state', () => {
    const state = {
        ...baseState,
        currentVotes: {},
        remainingVotes: null,
        players: {
            [playerOneKey]: {name:'Chuck', role:Roles.Spy, team:Teams.Blue},
            [playerTwoKey]: {name:'Pat', role:Roles.Spy, team:Teams.Blue}
        }
    }

    const result = vote(state, voteEventData);

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
            [playerOneKey]: {name:'Chuck', role:Roles.Spy, team:Teams.Blue},
            [playerTwoKey]: {name:'Pat', role:Roles.Spy, team:Teams.Blue}
        }
    }

    const eventData = {
        playerKey: playerTwoKey,
        playerName: 'test'
    }

    const result = vote(state, eventData);

    expect(result).toStrictEqual(votes)
})

test('player first vote', () => {
    const state = {
        ...baseState,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        players: {
            [playerOneKey]: {name:'Chuck', role:Roles.Spy, team:Teams.Blue},
            [playerTwoKey]: {name:'Pat', role:Roles.Spy, team:Teams.Blue}
        }
    }

    const result = vote(state, voteEventData);

    expect(result).toStrictEqual({
        currentVotes: {[playerTwoKey]: '02'},
        remainingVotes: [playerOneKey]
    });
})

test('player vote for card nominal', () => {

    const state = {
        ...baseState,
        currentVotes: {[playerOneKey]: '02'},
        remainingVotes: [playerTwoKey],
        players: {
            [playerOneKey]: {name:'Chuck', role:Roles.Spy, team:Teams.Blue},
            [playerTwoKey]: {name:'Pat', role:Roles.Spy, team:Teams.Blue}
        }
    }

    const result = vote(state, voteEventData);

    expect(result).toStrictEqual({
        currentVotes: {[playerOneKey]: '02', [playerTwoKey]: '02'},
        remainingVotes: []
    })
});

test('player vote for other card nominal', () => {

    const state = {
        ...baseState,
        currentVotes: {[playerOneKey]: '02', [playerTwoKey]: '02'},
        remainingVotes: [],
        players: {
            [playerOneKey]: {name:'Chuck', role:Roles.Spy, team:Teams.Blue},
            [playerTwoKey]: {name:'Pat', role:Roles.Spy, team:Teams.Blue}
        }
    }

    const result = vote(state, voteEventData);

    expect(result).toStrictEqual({
        currentVotes:  {[playerOneKey]: '02', [playerTwoKey]: '02'},
        remainingVotes: []
    })
})


// RETURN CARD

test('return a card nominal', () => {

    const state = {
        ...baseState,
        cards: [{x:2, y: 3, returned: false}],
        currentVotes: {[playerOneKey]: '02', [playerTwoKey]: '02'},
        remainingVotes: [],
        players: {
            [playerOneKey]: {name:'Chuck', role:Roles.Spy, team:Teams.Blue},
            [playerTwoKey]: {name:'Pat', role:Roles.Spy, team:Teams.Blue}
        }
    }

    const result = returnCard(state, returnEventData);

    expect(result).toMatchObject({
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        cards: [{x:2, y:3, returned: true}]
    })
})


// CONNECT PLAYER

test('player joined first', () => {
    const state = {
        ...baseState,
        currentTeam: Teams.Blue,
        remainingVotes: []
    };
    const evt = {
        ...addPlayerEvent,
        playerRole: Roles.Spy,
        playerTeam: Teams.Blue
    };

    const result = addNewPlayer(state, evt);

    expect(result).toStrictEqual({
        players: {[evt.playerKey]: {name:evt.playerName, team:evt.playerTeam, role:evt.playerRole}},
        remainingVotes: [evt.playerKey],
        currentVotes: {}
    });
})

test('player joined nominal', () => {
    const playerOne = {name:'PLAYER_TEST'+playerOneKey, role:Roles.Spy,team:Teams.Blue}
    const state = {
        ...baseState,
        players: {[playerOneKey]: playerOne},
        remainingVotes: [playerOneKey],
        currentTeam: Teams.Blue
    };
    const evt = {
        ...addPlayerEvent,
        playerTeam: Teams.Blue,
        playerRole: Roles.Spy
    };

    const result = addNewPlayer(state, evt)

    expect(result).toStrictEqual({
        players: {
                [playerOneKey]: playerOne,
                [evt.playerKey]: {name:evt.playerName, role:evt.playerRole,team:evt.playerTeam}
            },
        remainingVotes: [playerOneKey, evt.playerKey],
        currentVotes: {}
    })
})

test('player joined with an invalid state', () => {
    const playerOne = {name:'PLAYER_TEST'+playerOneKey, role:Roles.Spy,team:Teams.Blue}
    const state = {
        ...baseState,
        players: {[playerOneKey]: playerOne},
        remainingVotes: [playerOneKey],
        currentTeam: Teams.Blue,
        currentVotes: {}
    };

    const result = addNewPlayer(state, addPlayerEvent);

    expect(state).toMatchObject(result);
})


test('new player already joined', () => {
    const state = {
        ...baseState,
        players: { 
            [addPlayerEvent.playerKey]: {name:addPlayerEvent.playerName, role:addPlayerEvent.playerRole,team:addPlayerEvent.playerTeam}
        },
        remainingVotes: [addPlayerEvent.playerKey]
    }

    const result = addNewPlayer(state, addPlayerEvent);

    expect(result).toStrictEqual({
        players: {
            [addPlayerEvent.playerKey]: {name:addPlayerEvent.playerName, role:addPlayerEvent.playerRole,team:addPlayerEvent.playerTeam}
        },
        remainingVotes: [playerThreeKey],
        currentVotes: {}
    })
})

test('new player already voted', () => {
    const state = {
        ...baseState,
        players: { 
            [addPlayerEvent.playerKey]: {name:addPlayerEvent.playerName, role:addPlayerEvent.playerRole,team:addPlayerEvent.playerTeam}
        },
        remainingVotes: [],
        currentVotes: {[addPlayerEvent.playerKey]: '02'}
    }

    const result = addNewPlayer(state, addPlayerEvent);

    expect(result).toStrictEqual({
        players: {
            [addPlayerEvent.playerKey]: {name:addPlayerEvent.playerName, role:addPlayerEvent.playerRole,team:addPlayerEvent.playerTeam}
        },
        remainingVotes: [],
        currentVotes: state.currentVotes
    })
})

test('master spy joined', () => {
    const state = {
        ...baseState,
        players: {},
        remainingVotes: [],
        currentVotes: {}
    }

    const evt = { ...addPlayerEvent, playerRole: Roles.Master , playerTeam: Teams.Blue};
    const result = addNewPlayer(state, evt);

    expect(result).toStrictEqual({
        players: {
            [evt.playerKey]: {name:evt.playerName, role:evt.playerRole,team:evt.playerTeam}
        },
        remainingVotes: state.remainingVotes,
        currentVotes: state.currentVotes
    })
})

test('red player joined during blue turn', () => {
    const state = {
        ...baseState,
        currentTeam: Teams.Blue,
        remainingVotes: [],
        currentVotes: {}
    }
    const evt = {
        ...addPlayerEvent,
        playerTeam: Teams.Red,
        playerRole: Roles.Spy
    };

    const result = addNewPlayer(state, evt);

    expect(result).toStrictEqual({
        players: {
            [evt.playerKey]: {name:evt.playerName, role:evt.playerRole,team:evt.playerTeam}
        },
        remainingVotes: state.remainingVotes,
        currentVotes: state.currentVotes
    })
})


// PASS TURN

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
            currentTeam: Teams.Blue,
            isMyTurn: true
        },
        {
            remainingVotes: [playerOneKey, playerTwoKey]
        })
    ).toMatchObject({
        currentTeam: Teams.Red,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: false,
        isMyTurn: false
    })

    expect(passTurn(
        {
            ...baseState,
            canPassTurn: false,
            remainingVotes: [],
            currentTeam: Teams.Red,
            isMyTurn: false
        },
        {
            remainingVotes: [playerOneKey, playerTwoKey],
        })
    )
    .toMatchObject({
        currentTeam: Teams.Blue,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: false,
        isMyTurn: false
    })

})

test('passTurn with votes', () => {
    expect(
        passTurn(
        {
            ...baseState,
            players: [],
            currentVotes: {[playerOneKey]: '33'},
            currentTeam: 1
        },
        {
            remainingVotes: [playerOneKey, playerTwoKey],
        })
    )
    .toMatchObject(
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
    })).toMatchObject({
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
    })).toMatchObject({
        currentTeam: Teams.Blue,
        currentVotes: {},
        remainingVotes: [playerOneKey, playerTwoKey],
        canPassTurn: false
    })

})