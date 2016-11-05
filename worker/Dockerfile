FROM bshaffer/faceswap
MAINTAINER Brent Shaffer <bshafs@gmail.com>

COPY . /app

WORKDIR /app

RUN pip install -r requirements.txt

EXPOSE 8080
ENV PORT 8080

ENTRYPOINT ["gunicorn", "-b", ":8080", "main:app"]
#ENTRYPOINT ["python", "main.py"]
