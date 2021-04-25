import React from 'react'
import renderer from 'react-test-renderer'
import Game from '../game'

jest.mock('../DataSource')

it('game UI renders correctly', () => {
    const gameKey = '123'
    const playerKey = '456'
    const tree = renderer
        .create(<Game gameKey={gameKey} playerKey={playerKey}></Game>)
        .toJSON()
    expect(tree).toMatchSnapshot()
});