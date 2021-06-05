import React, {Component} from 'react'
import { Colors } from './colors';

export class Schema extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
        }
    }

    render() {
        const sq = this.props.cards.map(c => {  
            return {
                color: c.color, 
                x: c.x, 
                y: c.y
            }
        })

        return (
            <div      
                key='cn-schema-row'
                id='cn-schema'
            >
                <div className='row'>
                        {sq.map(c => {
                            const attr = {
                                key: c.x+'-'+c.y,
                                src: '',
                                className: 'cn-schema-img'
                            }

                            const src = 'images/schema-'
                            attr.src = src
                                + {
                                [Colors.Blue]: 'blue',
                                [Colors.Red]: 'red',
                                [Colors.Black]: 'black',
                                [Colors.White]: 'white'
                                }[c.color]
                                + '.png'

                            return <img 
                                key={attr.key} 
                                src={attr.src}
                                className={attr.className}
                            />
                            }
                        )}
                </div>
            </div>
        )
    }

}