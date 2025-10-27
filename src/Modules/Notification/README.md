# Notification Module

**Bounded Context**: Multi-Channel Notifications

## Responsibilities

- Send notifications across multiple channels (email, SMS, push, in-app)
- Manage notification preferences
- Track notification delivery status
- Handle notification templates
- Queue and retry failed notifications

## Dependencies

- **User Module**: Listens to all user events to send relevant notifications
- **Order Module**: Listens to order events to notify customers
- **Billing Module**: Listens to payment events to send receipts

## Public API (Integration Events)

- `NotificationWasSentIntegrationEvent` - Fired when notification is delivered
- `NotificationFailedIntegrationEvent` - Fired when notification cannot be delivered

## Database Schema

PostgreSQL schema: `notification_module`

Tables:
- `notification_module.notification_read_model` - Notification history
- `notification_module.preference_read_model` - User notification preferences
- `notification_module.template_read_model` - Notification templates

## Getting Started

This module listens to ALL integration events from other modules.

Example handlers:
- `SendWelcomeNotificationWhenUserCreated`
- `SendOrderConfirmationWhenOrderPlaced`
- `SendPaymentReceiptWhenPaymentProcessed`
- `SendLowStockAlertToAdmins`
