import React, {Component} from 'react'
import {Board} from './board';
import {GameInfo} from './gameInfo';
import { DataSource } from './dataSource';
import { Events } from './events';

export default class Game extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            gameKey: props.gameKey,
            playerKey: props.playerKey,
            playerTeam: props.playerTeam,
            isMyTurn: props.isMyTurn
        }
        this.subscriptions = 
        [
            PubSub.subscribe(Events.TURN_PASSED, (evt, data) => {
                this.setState({ 
                    team: data.team,
                    remainingVotes: data.voters || [],
                    announcedNumber: 0,
                    announcedWord: ""
                 })
            }),
            PubSub.subscribe(Events.GLOBAL_ERROR, (evt, data) => {
                this.setState({
                    displayError: true,
                    errorMessage: data.errorMessage
                })

                this.setState({
                    displayError: true,
                    errorMessage: data.errorMessage
                })
            })
        ]
    }

    componentDidMount() {
        // Discute avec le composant de données pour et met à jour l'état une fois
        // que la réponse est revenue
        const self = this;

        DataSource
            .get('/gameInfos', { gameKey: this.state.gameKey })
            .then(data => {

                if(typeof data === 'string' || typeof data === 'undefined') {
                    console.log('Mauvais format de paramètre dans le callback (game)')
                    return
                }
                if(data.error === true) {
                    console.log(data.message)
                    return
                }

                self.setState({
                    gameKey:            data.gameKey,
                    playerKey:          data.playerKey,
                    playerTeam:         data.playerTeam,
                    name:               data.playerName,
                    role:               data.playerRole,
                    currentTeam:        data.currentTeam,
                    announcedNumber:    data.currentNumber,
                    announcedWord:      data.currentWord,
                    remainingVotes:     data.remainingVotes,
                    isMyTurn:           data.currentTeam === data.playerTeam
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
                    name= {this.state.name}
                    isMyTurn={this.state.isMyTurn}
                />
                <GameInfo 
                    gameKey={this.state.gameKey} 
                    playerKey={this.state.playerKey}
                    playerTeam={this.state.playerTeam}
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