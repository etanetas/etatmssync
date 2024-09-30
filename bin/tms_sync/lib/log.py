import logging
import logging.handlers


def getLogger(logger_name="tms_sync", level=logging.INFO):
    logger = logging.getLogger(logger_name)
    if not logger.hasHandlers():
        logger.setLevel(level)
        syslog_handler = logging.handlers.SysLogHandler(address='/dev/log')
        formatter = logging.Formatter(
            '%(asctime)s %(name)s: %(levelname)s %(message)s', 
            datefmt='%Y-%m-%d %H:%M:%S'  # Customize the date format
        )
        logger.addHandler(syslog_handler)
    return logger
