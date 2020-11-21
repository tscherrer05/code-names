import React, {Component} from 'react'
import {Events} from './events';
import { Roles } from './roles';
import { Teams } from './teams';

export class GameInfo extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
        }
    }

    passTurn() {
        PubSub.publish(Events.PASS_TURN, {
            gameKey:    this.props.gameKey,
            playerKey:  this.props.playerKey
        })
    }


    render() {

        const PRIMARY_COLOR = 'badge-success'
        const SECONDARY_COLOR = 'badge-secondary'
        var currentPlayerKey = this.props.playerKey;

        // Connected players
        debugger
        const playerModels = Object.entries(this.props.players || [])
        const players = 
            playerModels.map(([key, model]) => {
                let color
                if(model.team === Teams.Blue) {
                    color = "blue"
                } else {
                    color = "red"
                }
                return <li 
                    id={'player-'+key} 
                    key={'player-'+key} 
                    style={{color: model.team === Teams.Blue ? 'blue' : 'red'}}>
                        {model.name} ({model.role === Roles.Spy ? 'Espion' : 'Agent'})
                    </li>
        })

        // Remaining votes
        const votes = this.props.remainingVotes
                        ?.map(playerKey => {
                            return { 
                                key:    playerKey,
                                name:   this.props.players[playerKey].name,
                                color:  playerKey == currentPlayerKey ? PRIMARY_COLOR : SECONDARY_COLOR
                            }
                        })
                        ?.map(p => <span id={'vote-tag-'+p.key} key={'vote-tag-'+p.key} className={'badge '+p.color}>{p.name}</span>)

        // Next turn button
        let nextTurnButton = this.props.canPassTurn
                        ?   (
                                <div className="row">
                                    <button onClick={() => this.passTurn()}>Passer le tour</button>
                                </div>
                            )
                        : null
        

        // Labels
        let playerTeam = this.props.playerTeam == 1
                        ? (<span style={{color: "blue"}}>Bleue</span>)
                        : (<span style={{color: "red"}}>Rouge</span>)

        let currentTeam = this.props.currentTeam == 1
                            ? (<span style={{color: "blue"}}>Bleue</span>)
                            : (<span style={{color: "red"}}>Rouge</span>)

        return (
            <div className="container">
                <div className="row">
                    <div className="col">
                        <p>Vous êtes :&nbsp;
                            <span id="current-player" data-value={this.props.name}>{this.props.name}</span>
                        </p>
                        <p>Votre équipe :&nbsp;
                            {playerTeam}
                        </p>
                        <p>Votre rôle :&nbsp;
                            {this.props.role == 1 ? "Espion" : "Maître espion"}
                        </p>
                    </div>
                    <div className="col">
                        <p>Tour :&nbsp;
                            <span id="current-team">Equipe {currentTeam}</span>
                        </p>
                        <span id="gameKey" data-value={this.props.gameKey}></span>
                        <span id="playerKey" data-value={this.props.playerKey}></span>
                    </div>
                    <div className="col">
                        <p className="display-block">Votes restants :</p>
                        {votes}
                    </div>
                    <div className="col">
                        <p className="display-bloc">Joueurs connectés :</p>
                        <ul>
                            {players}
                        </ul>
                    </div>
                </div>
                {nextTurnButton}
            </div>
        );
    }
}