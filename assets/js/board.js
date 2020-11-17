import React, {Component} from 'react'
import {Card} from './card';
import { Roles } from './roles';

export class Board extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
        }
    }

    render() {

        const cardVotes = {}
        const votes = this.props.currentVotes || []
        for (var playerKey in votes) {
            if (votes.hasOwnProperty(playerKey)) {
                var cardKey = votes[playerKey]
                var playerName = this.props.players[playerKey].name
                if(cardVotes[cardKey] == null)
                    cardVotes[cardKey] = [{ key: playerKey, name: playerName}]   
                else
                    cardVotes[cardKey].push({ key: playerKey, name: playerName})
            }
        }

        return (
            <div>
                <div
                    key='cn-cards-row'
                    id='cn-cards-row'
                    className='row'>
                        {this.props.cards.map(c => {
                            return <Card
                                    key=        {c.x+'-'+c.y}
                                    name=       {c.name}
                                    color=      {c.color}
                                    x=          {c.x}
                                    y=          {c.y}
                                    returned =  {c.returned}
                                    gameKey=    {this.props.gameKey}
                                    playerKey=  {this.props.playerKey}
                                    votes=      {cardVotes[''+c.x+c.y] || []}
                                    isClickable={this.props.isMyTurn && this.props.role == Roles.Spy}
                                />
                            }
                        )}
                </div>
            </div>
        )
    }
}