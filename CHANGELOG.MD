# Changelog
## 0.2.1
- Fix bug with logout exception if the session is expired on the billing side before he token is expired
## 0.2
- Async mode implemented in Billing, see callAsync() method
- Async call run for a list of obects, implementing AsyncOperationInterface, AsyncOperation class may use out from the box
- Add callList() method to call and return the lost content if the list is only element of returned array  
## 0.1
- First usable code which really works
- Billing and ESPF classes implemented