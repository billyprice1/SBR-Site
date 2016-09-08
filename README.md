# Scammer Bingo Reborn Number API Docs

## Adding numbers

Adding numbers is one of the more complex things to do. The add URL is `/api/numbers.php?req=add`. This URL requires 1 arg, and has 1 optional arg.

Arguments:

- `data` - Required, The number, in the format: `+CCC NNNNNNNNNNNNN`, the C's being a country code, so for the US, it would be `001` and the N's being the number, at 13 characters max length
- `specifiedname` - Optional, The name you wish to use when submitting

Example output:
```json
{"success":true,"summary":{"country":"001","number":"12345678901234","ip":"xxx.xxx.xxx.xxx","name":"Your Name"}}
```

## Getting a list of numbers

Getting a list is quite simple. The get URL is `/api/numbers.php?req=get`. This URL requires no args.

Example output:
```json
{"success":true,"summary":{"limit":10},"data":[{"id":"13","country":"123","number":"45678901234","submitted_name":"DevJoe","submitted_date":"2016-09-07 18:48:10"}]}
```

## Reporting a number

**NOTE: ABUSING THIS FEATURE WILL GET YOU BLACKLISTED.**

The report URL is `/api/numbers.php?req=report`. This requires 1 arg, which is required.

Arguments:

- `id` - The ID of the number given during a get request

Example Output:
```json
{"success":true,"summary":{"ip":"xxx.xxx.xxx.xxx","type":"numbers","id":4}}
```
