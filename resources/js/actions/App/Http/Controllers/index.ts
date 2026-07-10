import Settings from './Settings'
import Customer from './Customer'
import Public from './Public'
import Driver from './Driver'
import Operator from './Operator'
import Admin from './Admin'

const Controllers = {
    Settings: Object.assign(Settings, Settings),
    Customer: Object.assign(Customer, Customer),
    Public: Object.assign(Public, Public),
    Driver: Object.assign(Driver, Driver),
    Operator: Object.assign(Operator, Operator),
    Admin: Object.assign(Admin, Admin),
}

export default Controllers