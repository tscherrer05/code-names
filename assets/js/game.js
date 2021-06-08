import React from 'react'
import {Board} from './board'
import { DataSource } from './dataSource'
import { Events } from './events'
import { Schema } from './schema'
import { returnCard, vote, addNewPlayer, passTurn } from './modules/game'
import { Roles } from './roles'
import Modal from 'react-bootstrap/Modal'
import PubSub from 'pubsub-js'
import { Teams } from './teams'
import { Colors } from './colors'

export default class Game extends React.Component {

    constructor(props) {
        super(props)
        this.state = {
            connecting: true,
            gameKey: props.gameKey,
            playerKey: props.playerKey,
            playerTeam: props.playerTeam,
            isMyTurn: props.isMyTurn,
            errorMessage: "",
            displayError: false,
            currentVotes: [],
            remainingVotes: [],
            cards: [],
            displayParameters: false,
            events: []
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
                        {
                            remainingVotes: data.remainingVotes,
                            team: data.team,
                            canPassTurn: data.canPassTurn
                        }
                    )
                )
            })
        ]
    }

    componentDidMount() {
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
                    connecting:         false,
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
                    canPassTurn:        data.canPassTurn,
                    cards:              data.cards.map(x => {
                        return {
                            color: x.color,
                            returned: x.returned,
                            name: x.word,
                            x: x.x,
                            y: x.y
                        }
                    })
                })
            })
            .catch((error) => {
                console.log('Error fetching data', error)
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

    handlePassTurn() {
        PubSub.publish(Events.PASS_TURN, {
            gameKey: this.state.gameKey,
            playerKey: this.state.playerKey
        })
    }

    getErrorMessageIfApplicable() {
        if (this.state.displayError) {
            return (
                <div style={{ position: 'fixed', left: '50%', top: '50%', zIndex: 1000 }}>
                    <div style={{
                        position: 'relative',
                        left: '-50%',
                        zIndex: 1000,
                        textAlign: 'center',
                        background: 'rgb(255, 228, 0)',
                        color: 'black',
                        animation: 'shake 0.5s',
                        padding: '7px',
                        borderRadius: '10px',
                        userSelect: 'none'
                    }}>
                        <h1>{this.state.errorMessage}</h1>
                    </div>
                </div>
            )
        } else {
            return null;
        }
    }

    getModalIfApplicable() {
        return (
            <Modal
                show={this.state.displayParameters}
                backdrop="static"
                keyboard={true}
                onHide={() => this.closeParameters()}
            >
            <Modal.Header closeButton>
                Menu
                    </Modal.Header>
            <Modal.Body>
                <button className='cn-button btn-block' onClick={() => this.resetGame()}>Réinitialiser la partie en cours</button>
                <button className='cn-button btn-block' onClick={() => this.emptyGame()}>Terminer la partie en cours</button>
            </Modal.Body>
        </Modal>)
    }

    getSchemaIfApplicable() {
        if (this.state.role == Roles.Master) {
            return (
                <div style={{ backgroundColor: 'rgba(0, 0, 0, 0)', border: 'none' }}>
                        <Schema cards={this.state.cards} />
                </div>
            )
        } else {
            return null
        }
    }

    getCurrentTeamIfApplicable() {
        if (this.state.currentTeam) {
            return <span style={{  }}>C'est le tour de l'équipe <span>{Colors.stringFromIntLocale(this.state.currentTeam, "fr")}</span></span>
        }
    }
    
    render() {
        if (this.state.connecting) {
            return (<h4 style={{ color: 'white', textAlign: 'center' }}>Connexion à la partie...</h4>)
        }

        // Connected players
        const playerModels = Object.entries(this.state.players || [])
        const toPlayerListItem = ([key, model]) => {
            if (this.state.playerKey == key)
                return <li
                    id={'player-' + key}
                    key={'player-' + key}>
                    {model.name} (vous)
                    </li>
            else
                return <li
                    id={'player-' + key}
                    key={'player-' + key}>
                    {model.name}
                </li>
        }
        const redSpies = playerModels
            .filter(([key, model]) => {
                if (model.team == Teams.Red && model.role == Roles.Spy)
                    return [key, model]
            }).map(toPlayerListItem)
        
        const redMaster = playerModels
            .filter(([key, model]) => {
                if (model.team == Teams.Red && model.role == Roles.Master)
                    return [key, model]
            }).map(toPlayerListItem)[0]
        
        const blueSpies = playerModels
            .filter(([key, model]) => {
                if (model.team == Teams.Blue && model.role == Roles.Spy)
                    return [key, model]
            }).map(toPlayerListItem)
        
        const blueMaster = playerModels
            .filter(([key, model]) => {
                if (model.team == Teams.Blue && model.role == Roles.Master)
                    return [key, model]
            }).map(toPlayerListItem)[0]
        
        // Next turn button
        const nextTurnButton = this.state.canPassTurn
            ? (<button className='cn-button' onClick={() => this.handlePassTurn()}>Finir le tour</button>)
            : null
        
        // Remaining votes
        const PRIMARY_COLOR = 'badge-success'
        const SECONDARY_COLOR = 'badge-secondary'
        var currentPlayerKey = this.state.playerKey;
        const votes = this.state.remainingVotes
            ?.map(playerKey => {
                return {
                    key: playerKey,
                    name: this.state.players[playerKey].name,
                    color: playerKey == currentPlayerKey ? PRIMARY_COLOR : SECONDARY_COLOR
                }
            })
            ?.map(p => <span id={'vote-tag-' + p.key} key={'vote-tag-' + p.key} className={'badge ' + p.color}>{p.name}</span>)

        return (
            <div>
                {this.getErrorMessageIfApplicable()}
                {this.getModalIfApplicable()}

                <div style={{display: 'flex', justifyContent:'space-between'}}>
                    <button className='cn-button' onClick={() => this.openParameters()}>Menu</button>
                    {nextTurnButton}
                </div>

                <div style={{display: 'flex', justifyContent: 'center', marginBottom: '10px' }}>
                    <span className='cn-message'>
                        {this.getCurrentTeamIfApplicable()}
                    </span>
                </div>

                <section style={{display: 'flex'}}>
                    <div style={{ flex: '1 1 100px', padding: '10px', background: 'radial-gradient(ellipse at left bottom, rgb(107, 17, 17), rgb(187, 31, 31))', borderRadius: '.5em', color: 'white' }}>
                        <h5>Maître-espion</h5>
                        <ul style={{ listStyleType: 'none', paddingLeft: '0' }}>
                            {redMaster}
                        </ul>
                        <h5>Agents</h5>
                        <ul style={{ listStyleType: 'none', paddingLeft: '0' }}>
                            {redSpies}
                        </ul>
                    </div>
                    <div style={{ flex: 'auto', display: 'flex', justifyContent: 'center' }}>
                        <Board
                            gameKey={this.state.gameKey}
                            playerKey={this.state.playerKey}
                            name={this.state.name}
                            role={this.state.role}
                            isMyTurn={this.state.isMyTurn}
                            players={this.state.players}
                            currentVotes={this.state.currentVotes}
                            cards={this.state.cards}
                        />
                    </div>
                    <div style={{ flex: '1 1 100px', padding: '10px', background: 'radial-gradient(ellipse at right bottom, rgb(10, 10, 80), rgb(26, 26, 185))', borderRadius: '.5em', color: 'white'  }}>
                        <h5>Maître-espion</h5>
                        <ul style={{ listStyleType: 'none', paddingLeft: '0' }}>
                            {blueMaster}
                        </ul>
                        <h5>Agents</h5>
                        <ul style={{ listStyleType: 'none', paddingLeft: '0' }}>
                            {blueSpies}
                        </ul>
                    </div>
                </section>
                <div style={{ display: 'flex', justifyContent: 'space-evenly', marginTop: '10px' }}>
                    {this.getSchemaIfApplicable()}
                    <div style={{ color: 'white', backgroundColor: 'rgba(91, 90, 90, 0.74)', padding: '10px', borderRadius: '.5em' }}>
                        <h5>Votes restants</h5>
                        {votes}
                    </div>
                    <div style={{ color: 'white', backgroundColor: 'rgba(91, 90, 90, 0.74)', padding: '10px', borderRadius: '.5em', flex: '0 1 300px' }}>
                        <h5>Historique</h5>
                        <ul style={{listStyleType: 'none', paddingLeft: '0', fontSize: '.7em'}}>
                            {this.state.events.map((evt) => <li id={evt.key} key={evt.key}>{evt.text}</li>)}
                        </ul>
                    </div>
                </div>
            </div>
        )
     }
}