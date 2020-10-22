import React from 'react'
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
            isMyTurn: props.isMyTurn,
            errorMessage: "",
            displayError: false
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
                    errorMessage: data.message
                })
                setTimeout(() => {
                    this.setState({
                        displayError: false,
                        errorMessage: data.message
                    })
                }, 4000)
            }),
            PubSub.subscribe(Events.HAS_VOTED, (evt, data) => {
                var filteredVotes = Object.values(this.state.remainingVotes).filter(v => {
                    return v.playerKey !== data.playerKey;
                })
                this.setState({
                    remainingVotes: filteredVotes
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
                    console.error('Mauvais format de paramètre dans le callback (game)')
                    return
                }
                if(data.error === true) {
                    console.error(data.message)
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
                    isMyTurn:           data.currentTeam === data.playerTeam,
                    canPassTurn:        data.canPassTurn
                })
            })
    }

    componentWillUnmount() {
        this.subscriptions.forEach(PubSub.unsubscribe);
    }

     render() {
        var errorMessage;
        if(this.state.displayError)
        {
            errorMessage = (
                <div style={{position: 'absolute', left: '50%', top: '50%'}}>
                    <div style={{
                        position: 'relative', 
                        left: '-50%', 
                        zIndex: 1000,
                        textAlign: 'center', 
                        background: '#7b2d26',
                        color: 'whitesmoke',
                        animation: 'shake 0.5s',
                        padding: '7px',
                        borderRadius: '10px'
                        }}>
                        <h1>{this.state.errorMessage}</h1>
                    </div>
                </div>
            )
        }

        return (
            <div>
                {errorMessage}
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
                    canPassTurn={this.state.canPassTurn}
                />
            </div>
        )
     }
}