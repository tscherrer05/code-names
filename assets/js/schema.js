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
                id='cn-schema'>
                <div className='row'>
                        {sq.map(c => {
                                const attr = {
                                    key: c.x+'-'+c.y,
                                    src: '',
                                    className: 'cn-schema-img'
                                }

                                let src = 'images/schema-'
                                switch(c.color) {
                                    case Colors.Blue:
                                        src += 'blue'
                                        break
                                    case Colors.Red:
                                        src += 'red'
                                        break
                                    case Colors.White:
                                        src += 'white'
                                        break
                                    case Colors.Black:
                                        src += 'black'
                                        break
                                    default:
                                }

                                attr.src = src+'.png'

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