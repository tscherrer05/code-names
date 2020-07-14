import React, {Component} from 'react'
import {Board} from './board';
import {GameInfo} from './gameInfo';
import { DataSource } from './dataSource';

export default class Game extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            gameKey: props.gameKey,
            playerKey: props.playerKey,
        }
        this.subscriptions = [

        ]
    }

    componentDidMount() {
        // Discute avec le composant de données pour et met à jour l'état une fois
        // que la réponse est revenue
        const self = this;

        DataSource
            .get('/gameInfos', { gameKey: this.state.gameKey })
            .then(data => {
                self.setState({
                    gameKey:            data['gameKey'],
                    playerKey:          data['playerKey'],
                    name:               data['playerName'],
                    role:               data['role'],
                    currentTeam:        data['currentTeam'],
                    announcedNumber:    data['currentNumber'],
                    announcedWord:      data['currentWord'],
                    remainingVotes:     data['remainingVotes'] || []
                })
            })
    }

    componentWillUnmount() {
        this.subscriptions.forEach(PubSub.unsubscribe);
    }

     render() {
         return (
            <div>
                <Board 
                    gameKey={this.state.gameKey} 
                    playerKey={this.state.playerKey}
                    currentPlayerName= {this.state.currentPlayerName}
                />
                <GameInfo 
                    gameKey={this.state.gameKey} 
                    playerKey={this.state.playerKey}
                    name={this.state.name}
                    role={this.state.role}
                    currentTeam={this.state.currentTeam}
                    announcedNumber={this.state.announcedNumber}
                    announcedWord={this.state.announcedWord}
                    remainingVotes={this.state.remainingVotes}
                />
          </div>
         )
     }
}