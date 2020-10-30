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
        })
    }

    render() {
        var currentPlayerKey = this.props.playerKey;
        
        // Votes zone
        // TODO ; Improve this part so we don't have to make so many checks
        var remainingVotes;
        if(this.props.remainingVotes === null || typeof(this.props.remainingVotes) === 'undefined')
            remainingVotes = [];
        else if(!Array.isArray(this.props.remainingVotes))
            remainingVotes = Object.values(this.props.remainingVotes);
        else
            remainingVotes = this.props.remainingVotes;
        const votes = remainingVotes.map(p => {
            if(p.key === currentPlayerKey)
                return <span id={'vote-tag-'+p.key} key={'vote-tag-'+p.key} className="badge badge-success">{p.name}</span>
            else
                return <span id={'vote-tag-'+p.key} key={'vote-tag-'+p.key}  className="badge badge-secondary">{p.name}</span>
        })

        // Next turn button
        let nextTurn = null;
        if(this.props.canPassTurn) {
            nextTurn = (
                <div className="row">
                    <button onClick={() => this.passTurn()}>Passer le tour</button>
                </div>
            )
        }

        // Labels
        let playerTeam = null;
        if(this.props.playerTeam == 1) {
            playerTeam = (<span style={{color: "blue"}}>Bleue</span>);
        } else {
            playerTeam = (<span style={{color: "red"}}>Rouge</span>);
        }
        let currentTeam = null;
        if(this.props.currentTeam == 1) {
            currentTeam = (<span style={{color: "blue"}}>Bleue</span>);
        } else {
            currentTeam = (<span style={{color: "red"}}>Rouge</span>);
        }

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
                {nextTurn}
            </div>
        );
    }
}