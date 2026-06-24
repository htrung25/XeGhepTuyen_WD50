import Dusk from './Dusk'
import Fortify from './Fortify'
import Sanctum from './Sanctum'

const Laravel = {
    Dusk: Object.assign(Dusk, Dusk),
    Fortify: Object.assign(Fortify, Fortify),
    Sanctum: Object.assign(Sanctum, Sanctum),
}

export default Laravel