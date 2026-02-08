# Missing APIs from Requirements

Below is a list of APIs from your requirements that are NOT currently implemented in your Laravel project (based on routes/api.php and apiendpoints.md):





## 3. Subscription & Billing APIs
- GET /plans  
- POST /tenants/{tenantId}/subscriptions  
- PUT /tenants/{tenantId}/subscriptions  
- DELETE /tenants/{tenantId}/subscriptions  
- POST /billing/payment-methods  
- GET /billing/invoices  
- POST /billing/webhooks  





## 6. Data Isolation & Storage APIs
- GET /data/export  
- POST /data/import  
- POST /data/migrate  
- DELETE /tenants/{tenantId}/data  

## 7. Audit, Logs & Compliance APIs
- GET /audit-logs  
- GET /tenants/{tenantId}/audit-logs  
- GET /activity-logs  
- POST /compliance/gdpr/export  
- POST /compliance/gdpr/delete  

## 8. Notification & Communication APIs
- POST /notifications/email  
- POST /notifications/sms  
- POST /notifications/in-app  
- GET /notification-templates  
- PUT /tenants/{tenantId}/notification-settings  

## 9. Admin / Super-Admin APIs
- GET /admin/tenants  
- GET /admin/tenants/{tenantId}/usage  
- POST /admin/tenants/{tenantId}/suspend  
- POST /admin/impersonate-user  



## 11. Integration & Webhook APIs
- POST /tenants/{tenantId}/integrations  
- GET /integrations  
- POST /webhooks  
- GET /webhooks/events  

---

**Note:**
- Endpoints listed as missing are not found in your current routes/api.php or apiendpoints.md.
- If you have implemented any of these in other files or via dynamic routing, please update this list accordingly.
