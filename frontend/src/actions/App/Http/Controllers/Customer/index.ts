import TripSearchController from './TripSearchController'
import AuthController from './AuthController'
import PaymentController from './PaymentController'
import TrackingController from './TrackingController'
import BookingController from './BookingController'
import WalletController from './WalletController'
import VoucherController from './VoucherController'
import ReviewController from './ReviewController'
import NotificationController from './NotificationController'

const Customer = {
    TripSearchController: Object.assign(TripSearchController, TripSearchController),
    AuthController: Object.assign(AuthController, AuthController),
    PaymentController: Object.assign(PaymentController, PaymentController),
    TrackingController: Object.assign(TrackingController, TrackingController),
    BookingController: Object.assign(BookingController, BookingController),
    WalletController: Object.assign(WalletController, WalletController),
    VoucherController: Object.assign(VoucherController, VoucherController),
    ReviewController: Object.assign(ReviewController, ReviewController),
    NotificationController: Object.assign(NotificationController, NotificationController),
}

export default Customer