import React, {Component} from 'react'
import {Events} from './events';

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
        console.log('Render gameInfo')

        const PRIMARY_COLOR = 'badge-success'
        const SECONDARY_COLOR = 'badge-secondary'
        var currentPlayerKey = this.props.playerKey;

        const votes = this.props.remainingVotes
                        ?.map(playerKey => {
                            return { 
                                key:    playerKey,
                                name:   this.props.players[playerKey],
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
                            {this.props.role == 1 ? "Espion" : "Maître espion"}</p>
                    </div>
                    <div className="col">
                        <p>Tour :&nbsp;
                            <span id="current-team">Equipe {currentTeam}</span>
                        </p>
                        <p>Mot annoncé :&nbsp;
                            <span id="announced-word">{this.props.announcedWord}
                            </span>
                        </p>
                        <p>Nombre annoncé :&nbsp;
                            <span id="announced-number">{this.props.announcedNumber}</span>
                        </p>
                        <span id="gameKey" data-value={this.props.gameKey}></span>
                        <span id="playerKey" data-value={this.props.playerKey}></span>
                    </div>
                    <div className="col">
                        <p className="display-block">Votes restants :</p>
                        {votes}
                    </div>
                </div>
                {nextTurnButton}
            </div>
        );
    }
}