import AuthController from './AuthController'
import OnboardingController from './OnboardingController'
import RouteController from './RouteController'
import VehicleController from './VehicleController'
import DriverController from './DriverController'
import TripController from './TripController'
import BookingController from './BookingController'
import RevenueController from './RevenueController'

const Operator = {
    AuthController: Object.assign(AuthController, AuthController),
    OnboardingController: Object.assign(OnboardingController, OnboardingController),
    RouteController: Object.assign(RouteController, RouteController),
    VehicleController: Object.assign(VehicleController, VehicleController),
    DriverController: Object.assign(DriverController, DriverController),
    TripController: Object.assign(TripController, TripController),
    BookingController: Object.assign(BookingController, BookingController),
    RevenueController: Object.assign(RevenueController, RevenueController),
}

export default Operator