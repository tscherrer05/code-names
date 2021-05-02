import React from 'react'
import { cleanup, fireEvent, render } from '@testing-library/react'
import Game from '../game'

jest.mock('../DataSource')

it('Game displays correctly', () => {
    const gameKey = '123'
    const playerKey = '456'
    const { queryByText } = render(<Game gameKey={gameKey} playerKey={playerKey}></Game>)
    expect(queryByText("Votre équipe :")).toBeDefined()
})