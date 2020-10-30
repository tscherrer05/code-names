import React, {Component} from 'react'
import {DataSource} from './dataSource'
import {Events} from './events'
import PubSub from 'pubsub-js';
import {Card} from './card';

// Datasource mock
// DataSource.get = (params) => { 
//     return new Promise((resolve, reject) => { resolve([
//             {
//                 color: 2,
//                 word: 'test',
//                 x: 0,
//                 y: 0,
//                 returned: false,
//                 voters: []
//             },
//             {
//                 color: 2,
//                 word: 'AEDS',
//                 x: 0,
//                 y: 1,
//                 returned: false,
//                 voters: []
//             },
//             {
//                 color: 2,
//                 word: 'JDOFIJ',
//                 x: 0,
//                 y: 2,
//                 returned: false,
//                 voters: []
//             }
//         ]) 
//     })
// }

export class Board extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            gameKey:            props.gameKey,
            playerKey:          props.playerKey,
            cards:              props.cards,
        };
        const self = this;
        this.subscriptions = [
            PubSub.subscribe(Events.HAS_VOTED, (evt, data) => {
                if('error' in data) {
                    console.error(data['message'])
                    return
                }
                this.setState({
                    cards: self.state.cards.map(c => {
                        const voterTuple = {key: data.playerKey, name: data.playerName}
                        c.voters = c.voters.filter(v => { return v.key != voterTuple.key })
                        if(c.x === data.x && c.y === data.y) {
                            // Ajouter le nom du votant sur la carte désignée
                            c.voters.push(voterTuple)
                        } else {
                            // Retirer le nom du votant de toute autre carte
                            c.voters = c.voters.filter(v => { return v.key != voterTuple.key })
                        }
                        return c
                    })
                })
            })
        ]
    }

    componentWillUnmount() {
        this.subscriptions.forEach(PubSub.unsubscribe);
    }

    render() {
        return (
            <div>
                <div
                    key='cn-cards-row'
                    id='cn-cards-row'
                    className='row'>
                        {this.props.cards.map(c => 
                            <Card
                                key=        {c.x+'-'+c.y}
                                name=       {c.name}
                                color=      {c.color}
                                x=          {c.x}
                                y=          {c.y}
                                returned =  {c.returned}
                                gameKey=    {this.state.gameKey}
                                playerKey=  {this.state.playerKey}
                                votes=      {c.voters}
                                isClickable={this.props.isMyTurn}
                            />
                        )}
                </div>
            </div>
        );
    }
}