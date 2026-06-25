import AuthController from './AuthController'
import DashboardController from './DashboardController'
import OperatorController from './OperatorController'
import PartnerApplicationController from './PartnerApplicationController'
import DriverController from './DriverController'
import UserController from './UserController'
import BookingController from './BookingController'
import TripController from './TripController'
import FinanceController from './FinanceController'
import VoucherController from './VoucherController'
import AuditLogController from './AuditLogController'

const Admin = {
    AuthController: Object.assign(AuthController, AuthController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    OperatorController: Object.assign(OperatorController, OperatorController),
    PartnerApplicationController: Object.assign(PartnerApplicationController, PartnerApplicationController),
    DriverController: Object.assign(DriverController, DriverController),
    UserController: Object.assign(UserController, UserController),
    BookingController: Object.assign(BookingController, BookingController),
    TripController: Object.assign(TripController, TripController),
    FinanceController: Object.assign(FinanceController, FinanceController),
    VoucherController: Object.assign(VoucherController, VoucherController),
    AuditLogController: Object.assign(AuditLogController, AuditLogController),
}

export default Admin