import AuthController from './AuthController'
import NotificationController from './NotificationController'
import TripController from './TripController'
import CheckinController from './CheckinController'
import LocationController from './LocationController'
import EarningController from './EarningController'

const Driver = {
    AuthController: Object.assign(AuthController, AuthController),
    NotificationController: Object.assign(NotificationController, NotificationController),
    TripController: Object.assign(TripController, TripController),
    CheckinController: Object.assign(CheckinController, CheckinController),
    LocationController: Object.assign(LocationController, LocationController),
    EarningController: Object.assign(EarningController, EarningController),
}

export default Driver