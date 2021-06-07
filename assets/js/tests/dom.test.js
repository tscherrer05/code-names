import regeneratorRuntime from "regenerator-runtime" // Needed in order to use 'async' keyword with tests. Tests must be async in order to be able to 'await' assertions.

import {
    queryByText,
    findByText,
    findByAltText,
    waitFor,
    findAllByText,
    getByText,
    getAllByText,
    queryAllByText,
    queryByAltText
} from '@testing-library/dom'
import '@testing-library/jest-dom'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

import { Teams } from '../teams'
import { Roles } from '../roles'
import { Colors } from '../colors'
import { StartUp } from '../startUp'
import { webSocketServer } from '../webSocketServer'
import { Events } from '../events'
import PubSub from 'pubsub-js'

jest.mock('../DataSource')
jest.mock('../WebSocketServer')

let dataSourceStub
let rootElement
const gameKey = '072eae5f-1d47-4fc6-b452-411f76ec5b6e'
const playerKey1 = '3fa222bf-b828-4212-a1be-084663e4a55d'
const defaultServerData = {
    gameKey: gameKey,
    playerKey: playerKey1,
    playerTeam: Teams.Blue,
    playerRole: Roles.Spy,
    playerName: 'Inspecteur LeBlanco',
    currentTeam: Teams.Blue,
    currentNumber: null,
    currentWord: null,
    allPlayers: {[playerKey1]: { name: 'Inspecteur LeBlanco', role: Roles.Spy, team: Teams.Blue }},
    currentTeamSpies: [playerKey1],
    currentTeamPlayers: [],
    currentVotes: [],
    remainingVotes: [playerKey1],
    isMyTurn: true,
    canPassTurn: false,
}

beforeEach(() => {
    jest.clearAllMocks()
    dataSourceStub = require('../DataSource')
    rootElement = document.createElement('div')
    document.body.appendChild(rootElement)
})

it('Game displays with basic info', async () => {
    // Arrange
    dataSourceStub.__setData({
        ...defaultServerData,
        cards: [
            { word: "Mot de carte", x: 1, y: 1, color: Colors.Red, returned: false },
            { word: "AAA", x: 1, y: 2, color: Colors.Red, returned: false }
        ]
    })

    // Act
    await StartUp(rootElement, defaultServerData.gameKey, defaultServerData.playerKey)

    // Assert
    expect(queryAllByText(rootElement, defaultServerData.playerName)).toHaveLength(1)
    expect(queryByText(rootElement, 'Mot de carte')).toBeDefined()
    expect(queryByText(rootElement, 'AAA')).toBeDefined()
})

it('Clicking on a card, happy path', async () => {
    // Arrange
    dataSourceStub.__setData({
        ...defaultServerData,
        cards: [
            { word: "Mot de carte", x: 1, y: 1, color: Colors.Red, returned: false },
        ]
    })
    await StartUp(rootElement, defaultServerData.gameKey, defaultServerData.playerKey)
    const card = queryByAltText(rootElement, 'Mot de carte')

    // Act
    userEvent.click(card)

    // Assert
    await waitFor(() =>
        expect(webSocketServer.mock.instances[0].send).toHaveBeenCalledTimes(1)
    )
})

it('Clicking on a card during other team turn', async () => {
    // Arrange
    dataSourceStub.__setData({
        ...defaultServerData,
        cards: [
            { word: "Mot de carte", x: 1, y: 1, color: Colors.Red, returned: false },
        ],
        playerTeam: Teams.Blue,
        currentTeam: Teams.Red
    })
    await StartUp(rootElement, defaultServerData.gameKey, defaultServerData.playerKey)
    const card = queryByAltText(rootElement, 'Mot de carte')

    // Act
    userEvent.click(card)

    // Assert
    await waitFor(() =>
        expect(webSocketServer.mock.instances[0].send).toHaveBeenCalledTimes(0)
    )
})

it('Red team returns a card', async () => {
    // Arrange
    const cardData = { word: "Mot de carte", x: 1, y: 1, color: Colors.Red, returned: false }
    dataSourceStub.__setData({
        ...defaultServerData,
        cards: [cardData],
        playerTeam: Teams.Blue,
        currentTeam: Teams.Red
    })
    const expected = "L'équipe rouge a retourné la carte '" + cardData.word + "'"
    
    await StartUp(rootElement, defaultServerData.gameKey, defaultServerData.playerKey, () => {
        expect(queryByText(rootElement, expected)).toBeNull()

        // Act
        PubSub.publishSync(Events.CARD_RETURNED, {
            x: cardData.x,
            y: cardData.y,
            color: cardData.color,
            word: cardData.word,
            team: Teams.Red
        })
    })

    // Assert
    const evts = queryAllByText(rootElement, expected)
    expect(evts).toBeDefined()
    expect(evts.length).toBe(1)
    expect(evts[0].innerHTML).toBe(expected)
})

it('Blue team returns a card', async () => {
    // Arrange
    const cardData = { word: "Mot de carte", x: 1, y: 1, color: Colors.Red, returned: false }
    dataSourceStub.__setData({
        ...defaultServerData,
        cards: [cardData],
        playerTeam: Teams.Blue,
        currentTeam: Teams.Blue
    })
    const expected = "L'équipe bleue a retourné la carte '" + cardData.word + "'"

    await StartUp(rootElement, defaultServerData.gameKey, defaultServerData.playerKey, () => {
        expect(queryByText(rootElement, expected)).toBeNull()

        // Act
        PubSub.publishSync(Events.CARD_RETURNED, {
            x: cardData.x,
            y: cardData.y,
            color: cardData.color,
            word: cardData.word,
            team: Teams.Blue
        })
    })

    // Assert
    const evts = queryAllByText(rootElement, expected)
    expect(evts).toBeDefined()
    expect(evts.length).toBe(1)
    expect(evts[0].innerHTML).toBe(expected)
})