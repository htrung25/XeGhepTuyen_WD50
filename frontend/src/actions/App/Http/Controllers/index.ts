import Public from './Public'
import Customer from './Customer'
import Driver from './Driver'
import Operator from './Operator'
import Admin from './Admin'

const Controllers = {
    Public: Object.assign(Public, Public),
    Customer: Object.assign(Customer, Customer),
    Driver: Object.assign(Driver, Driver),
    Operator: Object.assign(Operator, Operator),
    Admin: Object.assign(Admin, Admin),
}

export default Controllers