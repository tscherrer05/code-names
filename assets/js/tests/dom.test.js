import React from 'react'
import { act, cleanup, render, unmountComponentAtNode } from '@testing-library/react'
import Game from '../game'
import regeneratorRuntime from "regenerator-runtime"; // Needed to use 'async' keyword with tests. Tests must be async in order to be able to 'await' assertions.

jest.mock('../DataSource')

it('Game displays with basic info', async () => {
    const gameKey = '123'
    const playerKey = '456'
    let mock = require('../DataSource')
    mock.__setData({
        gameKey: gameKey,
        playerName: 'Inspecteur LeBlanco',
        cards: []
    })

    const { queryByText, findByText } = render(<Game gameKey={gameKey} playerKey={playerKey}></Game>)
    expect(queryByText('Votre équipe :')).toBeDefined()
    expect(await findByText('Inspecteur LeBlanco')).toBeDefined()
})