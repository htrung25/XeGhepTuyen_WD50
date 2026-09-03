import AuthController from './AuthController'
import NotificationController from './NotificationController'
import DashboardController from './DashboardController'
import HistoryController from './HistoryController'
import OnboardingController from './OnboardingController'
import FareRateController from './FareRateController'
import RouteController from './RouteController'
import VehicleController from './VehicleController'
import DriverController from './DriverController'
import TripController from './TripController'
import BookingController from './BookingController'
import RevenueController from './RevenueController'
const Operator = {
    AuthController: Object.assign(AuthController, AuthController),
NotificationController: Object.assign(NotificationController, NotificationController),
DashboardController: Object.assign(DashboardController, DashboardController),
HistoryController: Object.assign(HistoryController, HistoryController),
OnboardingController: Object.assign(OnboardingController, OnboardingController),
FareRateController: Object.assign(FareRateController, FareRateController),
RouteController: Object.assign(RouteController, RouteController),
VehicleController: Object.assign(VehicleController, VehicleController),
DriverController: Object.assign(DriverController, DriverController),
TripController: Object.assign(TripController, TripController),
BookingController: Object.assign(BookingController, BookingController),
RevenueController: Object.assign(RevenueController, RevenueController),
}

export default Operator