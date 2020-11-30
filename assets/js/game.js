import React from 'react'
import {Board} from './board'
import {GameInfo} from './gameInfo'
import { DataSource } from './dataSource'
import { Events } from './events'
import { Schema } from './schema'
import { returnCard, vote, addNewPlayer, passTurn } from './modules/game'
import { Roles } from './roles'
import Modal from 'react-bootstrap/Modal';
import Button from 'react-bootstrap/Button';


export default class Game extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            gameKey: props.gameKey,
            playerKey: props.playerKey,
            playerTeam: props.playerTeam,
            isMyTurn: props.isMyTurn,
            errorMessage: "",
            displayError: false,
            currentVotes: [],
            remainingVotes: [],
            cards: [],
            displayParameters: false
        }

        this.subscriptions = 
        [
            PubSub.subscribe(Events.GLOBAL_ERROR, (evt, data) => {
                this.setState({
                    displayError: true,
                    errorMessage: data.message
                })
                setTimeout(() => {
                    this.setState({
                        displayError: false,
                        errorMessage: null
                    })
                }, 4000)
            }),
            PubSub.subscribe(Events.HAS_VOTED, (evt, data) => {
                const stateDiff = vote(this.state, data)
                this.setState(stateDiff)
            }),
            PubSub.subscribe(Events.CARD_RETURNED, (evt, data) => {
                this.setState({
                    displayError: true,
                    errorMessage: "Carte retournée !",
                    ...returnCard(this.state, data)
                })
                setTimeout(() => {
                    this.setState({
                        displayError: false,
                        errorMessage: null
                    })
                }, 4000)
            }),
            PubSub.subscribe(Events.PLAYER_JOINED, (evt, data) => {
                this.setState(
                    addNewPlayer(
                        this.state,
                        {   
                            playerKey: data.playerKey, 
                            playerName: data.playerName,
                            playerRole: data.playerRole,
                            playerTeam: data.playerTeam
                        }
                    )
                )
            }),
            PubSub.subscribe(Events.TURN_PASSED, (evt, data) => {            
                this.setState(
                    passTurn(
                        this.state, 
                        {remainingVotes: data.remainingVotes}
                    )
                )
            })
        ]
    }

    componentDidMount() {
        // Discute avec le composant de données pour et met à jour l'état une fois
        // que la réponse est revenue
        const self = this;

        DataSource
            .get('gameInfos', { gameKey: this.state.gameKey })
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
                    players:            data.allPlayers,
                    currentTeamSpies:   data.currentTeamSpies,
                    currentTeamPlayers: data.currentTeamPlayers,
                    currentVotes:       data.currentVotes,
                    remainingVotes:     data.remainingVotes,
                    isMyTurn:           data.currentTeam === data.playerTeam,
                    canPassTurn:        data.canPassTurn
                })
            })


        DataSource
            .get('cards', { gameKey: this.state.gameKey })
            .then(data => {
                self.setState(
                {
                    cards: data.map(x => {
                                        return {
                                            color: x.color,
                                            returned: x.returned,
                                            name: x.word,
                                            x: x.x,
                                            y: x.y
                                        }}),
                    currentVotes: this.state.currentVotes
                })
            })
    }

    componentWillUnmount() {
        this.subscriptions.forEach(PubSub.unsubscribe);
    }

    openParameters() {
        this.setState({
            displayParameters: true
        })
    }

    closeParameters() {
        this.setState({
            displayParameters: false
        })
    }

    resetGame() {
        PubSub.publish(Events.RESET_GAME, {
            gameKey: this.state.gameKey
        })
    }

    emptyGame() {
        PubSub.publish(Events.EMPTY_GAME, {
            gameKey: this.state.gameKey
        })
    }
    
    render() {
        var errorMessage;
        if(this.state.displayError)
        {
            errorMessage = (
                <div style={{position: 'fixed', left: '50%', top: '50%', zIndex: 1000}}>
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

        let schema = null
        if(this.state.role == Roles.Master) {
            schema = (
                <div className='col'>
                    <Schema cards={this.state.cards}/>
                </div>
            )
        }

        let parameters = (
            <Modal
                show={this.state.displayParameters}
                backdrop="static"
                keyboard={true}
                onHide={() => this.closeParameters()}
            >
                <Modal.Header closeButton>
                    Paramètres du jeu
                </Modal.Header>
                <Modal.Body>
                    <button className='btn btn-secondary btn-lg btn-block' onClick={() => this.resetGame()}>Recommencer la partie (POUR TOUS)</button>
                    <button className='btn btn-secondary btn-lg btn-block' onClick={() => this.emptyGame()}>Terminer la partie (POUR TOUS)</button>
                </Modal.Body>
            </Modal>
        )

        return (
            <div className='container-fluid'>
                {errorMessage}
                {parameters}
                <div className='row'>
                    <a href='#' onClick={() => this.openParameters()}>Paramètres</a>
                </div>
                <div className='row'>
                    {schema}
                    <div className='col'>
                        <Board 
                            gameKey={this.state.gameKey} 
                            playerKey={this.state.playerKey}
                            name= {this.state.name}
                            role={this.state.role}
                            isMyTurn={this.state.isMyTurn}
                            players={this.state.players}
                            currentVotes={this.state.currentVotes}
                            cards={this.state.cards}
                        />
                    </div>
                </div>
                <div className='row'>
                    <GameInfo 
                            gameKey={this.state.gameKey} 
                            playerKey={this.state.playerKey}
                            playerTeam={this.state.playerTeam}
                            name={this.state.name}
                            role={this.state.role}
                            currentTeam={this.state.currentTeam}
                            announcedNumber={this.state.announcedNumber}
                            announcedWord={this.state.announcedWord}
                            players={this.state.players}
                            remainingVotes={this.state.remainingVotes}
                            canPassTurn={this.state.canPassTurn}
                        />
                </div>
            </div>
        )
     }
}